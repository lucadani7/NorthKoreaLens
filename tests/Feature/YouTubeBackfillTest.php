<?php

namespace Tests\Feature;

use App\Contracts\SpeechSynthesizer;
use App\Jobs\GeneratePostAudio;
use App\Models\Category;
use App\Models\Source;
use App\Services\PostAudioService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SourceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Tests\TestCase;

class YouTubeBackfillTest extends TestCase
{
    use RefreshDatabase;

    private const KOREA_NOW_CHANNEL = 'UCR-L8oZkHzgDh1ahJhH2WDQ';

    private const NATIONAL_GEOGRAPHIC_CHANNEL = 'UCpVm7bg6pXKo1Pr6k5kxG9A';

    private const UPLOADS_PLAYLIST = 'UUUploadsPlaylist';

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
            'feed_url' => 'https://www.youtube.com/feeds/videos.xml?channel_id='.self::KOREA_NOW_CHANNEL,
            'category_id' => Category::where('slug', Category::KOREA_NOW)->value('id'),
            'keywords' => ['North Korea', 'Pyongyang'],
            'needs_review' => true,
        ]);
    }

    /** @return array<string, mixed> */
    private function videoDetails(
        string $id,
        string $title,
        string $description = '',
        string $duration = 'PT10M',
        string $channelId = self::KOREA_NOW_CHANNEL,
    ): array {
        return [
            'id' => $id,
            'snippet' => [
                'publishedAt' => '2026-03-14T10:00:00Z',
                'channelId' => $channelId,
                'title' => $title,
                'description' => $description,
                'thumbnails' => ['high' => ['url' => "https://i.ytimg.com/vi/{$id}/hqdefault.jpg"]],
            ],
            'contentDetails' => ['duration' => $duration],
            'statistics' => ['viewCount' => '100'],
        ];
    }

    public function test_older_videos_are_imported_as_drafts_across_pages(): void
    {
        $source = $this->koreaNow();

        Http::fake([
            'www.googleapis.com/youtube/v3/channels*' => Http::response(['items' => [
                ['contentDetails' => ['relatedPlaylists' => ['uploads' => self::UPLOADS_PLAYLIST]]],
            ]]),
            'www.googleapis.com/youtube/v3/playlistItems*' => Http::sequence()
                ->push([
                    'items' => [['contentDetails' => ['videoId' => 'AAAAAAAAAAA']]],
                    'nextPageToken' => 'page-2',
                ])
                ->push(['items' => [['contentDetails' => ['videoId' => 'BBBBBBBBBBB']]]]),
            'www.googleapis.com/youtube/v3/videos*' => Http::response(['items' => [
                $this->videoDetails('AAAAAAAAAAA', 'N. Korea&#39;s missile: what we know', 'North Korea fired…'),
                $this->videoDetails('BBBBBBBBBBB', 'Life in Pyongyang'),
            ]]),
        ]);

        $this->artisan('youtube:backfill', ['source' => 'korea-now'])
            ->expectsOutputToContain('2 uploads listed, 2 videos available, 2 keyword matches, 2 new drafts')
            ->assertSuccessful();

        $post = $source->posts()->where('guid', 'yt:video:AAAAAAAAAAA')->firstOrFail();
        $this->assertSame("N. Korea's missile: what we know", $post->title);
        $this->assertSame('https://www.youtube.com/watch?v=AAAAAAAAAAA', $post->original_url);
        $this->assertNull($post->published_at);
        $this->assertSame('2026-03-14', $post->source_published_at?->toDateString());

        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return str_contains($request->url(), '/youtube/v3/channels')
                && ($query['id'] ?? null) === self::KOREA_NOW_CHANNEL
                && ($query['key'] ?? null) === 'test-key';
        });
        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return str_contains($request->url(), '/youtube/v3/playlistItems')
                && ($query['playlistId'] ?? null) === self::UPLOADS_PLAYLIST
                && ($query['pageToken'] ?? null) === 'page-2';
        });
    }

    public function test_our_own_keyword_check_still_applies(): void
    {
        $source = $this->koreaNow();
        Http::fake([
            'www.googleapis.com/youtube/v3/channels*' => Http::response(['items' => [
                ['contentDetails' => ['relatedPlaylists' => ['uploads' => self::UPLOADS_PLAYLIST]]],
            ]]),
            'www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [['contentDetails' => ['videoId' => 'CCCCCCCCCCC']]],
            ]),
            'www.googleapis.com/youtube/v3/videos*' => Http::response(['items' => [
                $this->videoDetails('CCCCCCCCCCC', 'K-pop concert in Seoul'),
            ]]),
        ]);

        $this->artisan('youtube:backfill', ['source' => 'korea-now'])->assertSuccessful();

        $this->assertSame(0, $source->posts()->count());
    }

    public function test_videos_already_imported_by_the_feed_are_not_duplicated(): void
    {
        $source = $this->koreaNow();
        Http::fake([
            'www.googleapis.com/youtube/v3/channels*' => Http::response(['items' => [
                ['contentDetails' => ['relatedPlaylists' => ['uploads' => self::UPLOADS_PLAYLIST]]],
            ]]),
            'www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [['contentDetails' => ['videoId' => 'AAAAAAAAAAA']]],
            ]),
            'www.googleapis.com/youtube/v3/videos*' => Http::response(['items' => [
                $this->videoDetails('AAAAAAAAAAA', 'North Korea parade'),
            ]]),
        ]);

        $this->artisan('youtube:backfill', ['source' => 'korea-now'])->assertSuccessful();
        $this->artisan('youtube:backfill', ['source' => 'korea-now'])
            ->expectsOutputToContain('1 keyword matches, 0 new drafts')
            ->assertSuccessful();

        $this->assertSame(1, $source->posts()->count());
    }

    public function test_it_needs_a_youtube_channel_and_keywords_unless_it_imports_drafts(): void
    {
        Http::fake();
        Source::factory()->create(['slug' => 'nk-news', 'feed_url' => 'https://www.nknews.org/feed/']);
        $this->koreaNow()->update(['keywords' => null, 'needs_review' => false]);

        $this->artisan('youtube:backfill', ['source' => 'nk-news'])->assertFailed();
        $this->artisan('youtube:backfill', ['source' => 'korea-now'])->assertFailed();

        Http::assertNothingSent();
    }

    public function test_a_channel_without_keywords_or_shorts_is_imported_whole_as_drafts(): void
    {
        $source = $this->koreaNow();
        $source->update(['slug' => 'charles-north-korea', 'keywords' => null, 'skip_shorts' => true]);
        Http::fake([
            'www.googleapis.com/youtube/v3/channels*' => Http::response(['items' => [
                ['contentDetails' => ['relatedPlaylists' => ['uploads' => self::UPLOADS_PLAYLIST]]],
            ]]),
            'www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [
                    ['contentDetails' => ['videoId' => 'DDDDDDDDDDD']],
                    ['contentDetails' => ['videoId' => 'EEEEEEEEEEE']],
                ],
            ]),
            'www.googleapis.com/youtube/v3/videos*' => Http::response(['items' => [
                $this->videoDetails('DDDDDDDDDDD', 'How I escaped twice', duration: 'PT1H2M5S'),
                $this->videoDetails('EEEEEEEEEEE', 'A long interview', duration: 'PT4M'),
            ]]),
        ]);

        $this->artisan('youtube:backfill', ['source' => 'charles-north-korea'])
            ->expectsOutputToContain('2 uploads listed, 2 videos available, 2 keyword matches, 2 new drafts')
            ->assertSuccessful();

        $this->assertSame(2, $source->posts()->whereNull('published_at')->count());

        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return str_contains($request->url(), '/youtube/v3/playlistItems')
                && ! isset($query['q'])
                && isset($query['playlistId']);
        });
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/youtube/v3/search'));
    }

    public function test_national_geographic_backfill_filters_keyword_matches_as_external_documentary_drafts(): void
    {
        $this->seed(SourceSeeder::class);
        $source = Source::where('slug', 'national-geographic')->firstOrFail();
        $existing = $source->posts()->create([
            'category_id' => $source->importCategory()->id,
            'guid' => 'yt:video:DDDDDDDDDDD',
            'title' => 'An older North Korea documentary',
            'original_url' => 'https://www.youtube.com/watch?v=DDDDDDDDDDD',
            'language' => 'en',
            'published_at' => now()->subDay(),
            'source_published_at' => now()->subDay(),
        ]);
        $publishedAt = $existing->published_at;
        $videos = [
            $this->videoDetails('AAAAAAAAAAA', 'A changing regime', 'A rare look at PYONGYANG.', channelId: self::NATIONAL_GEOGRAPHIC_CHANNEL),
            $this->videoDetails('BBBBBBBBBBB', 'Wildlife in the Amazon', 'A jaguar hunts at night.', channelId: self::NATIONAL_GEOGRAPHIC_CHANNEL),
            $this->videoDetails('EEEEEEEEEEE', 'DPRK in three minutes', duration: 'PT3M', channelId: self::NATIONAL_GEOGRAPHIC_CHANNEL),
            $this->videoDetails('CCCCCCCCCCC', 'The KOREAN WAR explained', channelId: self::NATIONAL_GEOGRAPHIC_CHANNEL),
            $this->videoDetails('DDDDDDDDDDD', 'An older North Korea documentary', channelId: self::NATIONAL_GEOGRAPHIC_CHANNEL),
            $this->videoDetails('FFFFFFFFFFF', 'A journey across the Korean Peninsula', duration: 'PT3M30S', channelId: self::NATIONAL_GEOGRAPHIC_CHANNEL),
        ];

        Http::fake([
            'www.googleapis.com/youtube/v3/channels*' => Http::response(['items' => [
                ['contentDetails' => ['relatedPlaylists' => ['uploads' => self::UPLOADS_PLAYLIST]]],
            ]]),
            'www.googleapis.com/youtube/v3/playlistItems*' => Http::sequence()
                ->push([
                    'items' => array_map(
                        fn (string $id) => ['contentDetails' => ['videoId' => $id]],
                        ['AAAAAAAAAAA', 'BBBBBBBBBBB', 'EEEEEEEEEEE'],
                    ),
                    'nextPageToken' => 'page-2',
                ])
                ->push([
                    'items' => array_map(
                        fn (string $id) => ['contentDetails' => ['videoId' => $id]],
                        ['CCCCCCCCCCC', 'AAAAAAAAAAA', 'DDDDDDDDDDD', 'FFFFFFFFFFF'],
                    ),
                ]),
            'www.googleapis.com/youtube/v3/videos*' => Http::response(['items' => $videos]),
        ]);

        $this->artisan('youtube:backfill', ['source' => 'national-geographic'])
            ->expectsOutputToContain('6 uploads listed, 6 videos available, 4 keyword matches, 3 new drafts')
            ->assertSuccessful();

        $posts = $source->posts()->with('category')->get();
        $this->assertCount(4, $posts);

        foreach ($posts as $post) {
            $this->assertSame('documentaries', $post->category->slug);
            $this->assertSame($source->id, $post->source_id);
            $this->assertFalse($post->is_nklens_original);
            $this->assertFalse($post->isOriginalByNkLens());

            if ($post->guid !== 'yt:video:DDDDDDDDDDD') {
                $this->assertNull($post->published_at);
            }
        }

        $this->assertNotNull($existing->fresh()->published_at);
        $this->assertTrue($existing->fresh()->published_at->equalTo($publishedAt));
        $this->assertDatabaseMissing('posts', ['guid' => 'yt:video:BBBBBBBBBBB']);
        $this->assertDatabaseMissing('posts', ['guid' => 'yt:video:EEEEEEEEEEE']);
        Http::assertSentCount(4);
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/youtube/v3/search'));
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/youtube/v3/playlistItems')
            && str_contains($request->url(), 'pageToken=page-2'));
        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return str_contains($request->url(), '/youtube/v3/videos')
                && ($query['id'] ?? null) === 'AAAAAAAAAAA,BBBBBBBBBBB,EEEEEEEEEEE,CCCCCCCCCCC,DDDDDDDDDDD,FFFFFFFFFFF';
        });

        $post = $source->posts()->where('guid', 'yt:video:AAAAAAAAAAA')->firstOrFail();
        $synthesizer = Mockery::mock(SpeechSynthesizer::class);
        $synthesizer->shouldNotReceive('synthesize');

        (new GeneratePostAudio((int) $post->getKey(), 'en'))
            ->handle(new PostAudioService($synthesizer));

        $post->publish();
        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.source.name', 'National Geographic')
                ->where('post.source.forbidsAi', false)
                ->where('audioUrl', null)
            );
    }
}
