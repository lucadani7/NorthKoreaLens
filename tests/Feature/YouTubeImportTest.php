<?php

namespace Tests\Feature;

use App\Enums\SourceType;
use App\Feeds\FeedImporter;
use App\Feeds\FeedItem;
use App\Models\Source;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class YouTubeImportTest extends TestCase
{
    use RefreshDatabase;

    private const CHANNEL = 'UCv_zJYPJndI4JH4P_p-eY5w';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CategorySeeder::class);
        config(['services.youtube.key' => 'test-key']);
    }

    private function charles(): Source
    {
        return Source::factory()->create([
            'slug' => 'charles-north-korea',
            'type' => SourceType::Testimony,
            'feed_url' => 'https://www.youtube.com/feeds/videos.xml?channel_id='.self::CHANNEL,
            'keywords' => null,
            'needs_review' => true,
            'skip_shorts' => true,
        ]);
    }

    /** @return array<string, mixed> */
    private function video(string $id, string $title, string $channelId = self::CHANNEL): array
    {
        return [
            'id' => $id,
            'snippet' => [
                'publishedAt' => '2024-05-01T10:00:00Z',
                'channelId' => $channelId,
                'title' => $title,
                'description' => 'My story.',
                'thumbnails' => ['high' => ['url' => "https://i.ytimg.com/vi/{$id}/hqdefault.jpg"]],
            ],
        ];
    }

    public function test_chosen_videos_are_imported_as_drafts(): void
    {
        Http::fake(['www.googleapis.com/*' => Http::response(['items' => [
            $this->video('AAAAAAAAAAA', 'How I escaped North Korea twice'),
            $this->video('BBBBBBBBBBB', 'Someone else\'s video', 'UCxxxxxxxxxxxxxxxxxxxxxx'),
        ]])]);
        $source = $this->charles();

        $this->artisan('youtube:import', ['source' => 'charles-north-korea', 'links' => [
            'https://www.youtube.com/watch?v=AAAAAAAAAAA&t=10s',
            'https://youtu.be/BBBBBBBBBBB',
            'https://youtu.be/CCCCCCCCCCC',
            'https://example.com/not-a-video',
        ]])
            ->expectsOutputToContain('Imported: How I escaped North Korea twice')
            ->expectsOutputToContain("Not on {$source->name}'s channel")
            ->expectsOutputToContain('Not found on YouTube (private or removed?): CCCCCCCCCCC')
            ->expectsOutputToContain('Not a YouTube video link: https://example.com/not-a-video')
            ->assertSuccessful();

        $this->assertSame(['yt:video:AAAAAAAAAAA'], $source->posts()->pluck('guid')->all());
        $post = $source->posts()->firstOrFail();
        $this->assertNull($post->published_at);
        $this->assertSame('https://www.youtube.com/watch?v=AAAAAAAAAAA', $post->original_url);
        $this->assertSame('human-rights', $post->category->slug);

        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $query['id'] === 'AAAAAAAAAAA,BBBBBBBBBBB,CCCCCCCCCCC';
        });
    }

    public function test_a_video_is_not_imported_twice(): void
    {
        Http::fake(['www.googleapis.com/*' => Http::response(['items' => [
            $this->video('AAAAAAAAAAA', 'How I escaped North Korea twice'),
        ]])]);
        $source = $this->charles();
        $link = ['source' => 'charles-north-korea', 'links' => ['https://youtu.be/AAAAAAAAAAA']];

        $this->artisan('youtube:import', $link)->assertSuccessful();
        $this->artisan('youtube:import', $link)
            ->expectsOutputToContain('Already imported or deleted')
            ->assertSuccessful();

        $this->assertSame(1, $source->posts()->count());
    }

    public function test_it_needs_a_youtube_channel(): void
    {
        Http::fake();
        Source::factory()->create(['slug' => 'nk-news', 'feed_url' => 'https://www.nknews.org/feed/']);

        $this->artisan('youtube:import', ['source' => 'nk-news', 'links' => ['https://youtu.be/AAAAAAAAAAA']])
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_the_catalog_lists_the_channel_videos_without_shorts(): void
    {
        Storage::fake('local');
        $long = $this->video('AAAAAAAAAAA', 'How I escaped North Korea twice') + ['contentDetails' => ['duration' => 'PT1H2M5S'], 'statistics' => ['viewCount' => '1200']];
        $short = $this->video('BBBBBBBBBBB', 'Quick question') + ['contentDetails' => ['duration' => 'PT45S']];
        Http::fake([
            'www.googleapis.com/youtube/v3/search*' => Http::response(['items' => [
                ['id' => ['videoId' => 'AAAAAAAAAAA'], 'snippet' => ['title' => 'x']],
                ['id' => ['videoId' => 'BBBBBBBBBBB'], 'snippet' => ['title' => 'y']],
            ]]),
            'www.googleapis.com/youtube/v3/videos*' => Http::response(['items' => [$long, $short]]),
        ]);
        $this->charles();

        $this->artisan('youtube:catalog', ['source' => 'charles-north-korea', '--pages' => 1])
            ->expectsOutputToContain('1 videos written')
            ->assertSuccessful();

        $videos = json_decode((string) Storage::disk('local')->get('youtube/charles-north-korea.json'), true);
        $this->assertCount(1, $videos);
        $this->assertSame('How I escaped North Korea twice', $videos[0]['title']);
        $this->assertSame(62, $videos[0]['minutes']);
        $this->assertSame(1200, $videos[0]['views']);
        $this->assertFalse($videos[0]['already_imported']);
    }

    public function test_promotion_is_left_out_of_youtube_excerpts(): void
    {
        $item = fn (string $description) => new FeedItem(
            guid: 'yt:video:AAAAAAAAAAA',
            title: 'Test',
            link: 'https://www.youtube.com/watch?v=AAAAAAAAAAA',
            excerpt: null,
            imageUrl: null,
            publishedAt: null,
            description: $description,
        );

        $this->assertNull(FeedImporter::excerpt($item(
            'Help me be a voice for voiceless North Koreans and get access to fun bonus content by supporting me on Patreon https://www.patreon.com/x https://instagram.com/charlesnorthkorea',
        )));
        $this->assertSame(
            'Otto Warmbier, an American college student, visited North Korea in 2016.',
            FeedImporter::excerpt($item('Otto Warmbier, an American college student, visited North Korea in 2016. Want Charles to answer one of your questions on his podcast? Email him at: info@example.com')),
        );
        $this->assertSame(
            "Want more of Songmi's story?",
            FeedImporter::excerpt($item("Want more of Songmi's story? Pick up her book here: https://a.co/d/x Find her on instagram: https://www.instagram.com/x")),
        );
    }

    public function test_prune_keeps_the_chosen_drafts_and_cleans_their_excerpts(): void
    {
        $source = $this->charles();
        $keep = $source->posts()->create([
            'category_id' => $source->importCategory()->id, 'guid' => 'yt:video:AAAAAAAAAAA', 'title' => 'Keep me',
            'original_url' => 'https://www.youtube.com/watch?v=AAAAAAAAAAA', 'language' => 'en',
            'excerpt' => 'Help me be a voice for voiceless North Koreans and get access to fun bonus content by supporting me on Patreon https://www.patreon.com/x My story.',
        ]);
        $drop = $source->posts()->create([
            'category_id' => $source->importCategory()->id, 'guid' => 'yt:video:BBBBBBBBBBB', 'title' => 'Drop me',
            'original_url' => 'https://www.youtube.com/watch?v=BBBBBBBBBBB', 'language' => 'en',
        ]);
        $published = $source->posts()->create([
            'category_id' => $source->importCategory()->id, 'guid' => 'yt:video:CCCCCCCCCCC', 'title' => 'Published',
            'original_url' => 'https://www.youtube.com/watch?v=CCCCCCCCCCC', 'language' => 'en', 'published_at' => now(),
        ]);
        $file = tempnam(sys_get_temp_dir(), 'keep');
        file_put_contents($file, "# keep\nhttps://www.youtube.com/watch?v=AAAAAAAAAAA  # Keep me\n");

        $this->artisan('youtube:prune', ['source' => 'charles-north-korea', 'keep' => $file, '--dry-run' => true])
            ->expectsOutputToContain('Would delete: Drop me')
            ->assertSuccessful();
        $this->assertNotSoftDeleted($drop);

        $this->artisan('youtube:prune', ['source' => 'charles-north-korea', 'keep' => $file])
            ->expectsOutputToContain('Deleted 1 drafts, kept 1')
            ->assertSuccessful();

        $this->assertSoftDeleted($drop);
        $this->assertNotSoftDeleted($published);
        $this->assertSame('My story.', $keep->fresh()?->excerpt);

        file_put_contents($file, "# nothing here\n");
        $this->artisan('youtube:prune', ['source' => 'charles-north-korea', 'keep' => $file])->assertFailed();
        $this->assertNotSoftDeleted($keep);
    }
}
