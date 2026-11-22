<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Source;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YouTubeBackfillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CategorySeeder::class);
        config(['services.youtube.key' => 'test-key']);
    }

    private function koreaNow(): Source
    {
        return Source::factory()->create([
            'slug' => 'korea-now',
            'feed_url' => 'https://www.youtube.com/feeds/videos.xml?channel_id=UCR-L8oZkHzgDh1ahJhH2WDQ',
            'category_id' => Category::where('slug', Category::KOREA_NOW)->value('id'),
            'keywords' => ['North Korea', 'Pyongyang'],
            'needs_review' => true,
        ]);
    }

    /** @return array<string, mixed> */
    private function searchResult(string $id, string $title, string $description = ''): array
    {
        return [
            'id' => ['kind' => 'youtube#video', 'videoId' => $id],
            'snippet' => [
                'publishedAt' => '2026-03-14T10:00:00Z',
                'title' => $title,
                'description' => $description,
                'thumbnails' => ['high' => ['url' => "https://i.ytimg.com/vi/{$id}/hqdefault.jpg"]],
            ],
        ];
    }

    public function test_older_videos_are_imported_as_drafts_across_pages(): void
    {
        Http::fake([
            'www.googleapis.com/*' => Http::sequence()
                ->push(['items' => [$this->searchResult('AAAAAAAAAAA', 'N. Korea&#39;s missile: what we know', 'North Korea fired…')], 'nextPageToken' => 'page-2'])
                ->push(['items' => [$this->searchResult('BBBBBBBBBBB', 'Life in Pyongyang')]]),
        ]);
        $source = $this->koreaNow();

        $this->artisan('youtube:backfill', ['source' => 'korea-now'])
            ->expectsOutputToContain('2 videos found, 2 new drafts')
            ->assertSuccessful();

        $post = $source->posts()->where('guid', 'yt:video:AAAAAAAAAAA')->firstOrFail();
        $this->assertSame("N. Korea's missile: what we know", $post->title);
        $this->assertSame('https://www.youtube.com/watch?v=AAAAAAAAAAA', $post->original_url);
        $this->assertNull($post->published_at);
        $this->assertSame('2026-03-14', $post->source_published_at?->toDateString());

        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $query['channelId'] === 'UCR-L8oZkHzgDh1ahJhH2WDQ'
                && $query['q'] === '"North Korea"|Pyongyang'
                && $query['key'] === 'test-key';
        });
    }

    public function test_our_own_keyword_check_still_applies(): void
    {
        Http::fake(['www.googleapis.com/*' => Http::response([
            'items' => [$this->searchResult('CCCCCCCCCCC', 'K-pop concert in Seoul')],
        ])]);
        $source = $this->koreaNow();

        $this->artisan('youtube:backfill', ['source' => 'korea-now'])->assertSuccessful();

        $this->assertSame(0, $source->posts()->count());
    }

    public function test_videos_already_imported_by_the_feed_are_not_duplicated(): void
    {
        Http::fake(['www.googleapis.com/*' => Http::response([
            'items' => [$this->searchResult('AAAAAAAAAAA', 'North Korea parade')],
        ])]);
        $source = $this->koreaNow();

        $this->artisan('youtube:backfill', ['source' => 'korea-now'])->assertSuccessful();
        $this->artisan('youtube:backfill', ['source' => 'korea-now'])
            ->expectsOutputToContain('1 videos found, 0 new')
            ->assertSuccessful();

        $this->assertSame(1, $source->posts()->count());
    }

    public function test_it_needs_a_youtube_channel_and_keywords(): void
    {
        Http::fake();
        Source::factory()->create(['slug' => 'nk-news', 'feed_url' => 'https://www.nknews.org/feed/']);
        $this->koreaNow()->update(['keywords' => null]);

        $this->artisan('youtube:backfill', ['source' => 'nk-news'])->assertFailed();
        $this->artisan('youtube:backfill', ['source' => 'korea-now'])->assertFailed();

        Http::assertNothingSent();
    }
}
