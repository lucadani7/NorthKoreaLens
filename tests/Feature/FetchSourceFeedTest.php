<?php

namespace Tests\Feature;

use App\Enums\SourceType;
use App\Jobs\FetchSourceFeed;
use App\Models\Post;
use App\Models\Source;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FetchSourceFeedTest extends TestCase
{
    use RefreshDatabase;

    private const FEED = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0">
          <channel>
            <item>
              <title>First article</title>
              <link>https://example.com/first</link>
              <guid>first</guid>
              <pubDate>Mon, 02 Nov 2026 08:30:00 +0000</pubDate>
              <description>First excerpt.</description>
            </item>
            <item>
              <title>Second article</title>
              <link>https://example.com/second</link>
              <guid>second</guid>
            </item>
          </channel>
        </rss>
        XML;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CategorySeeder::class);
    }

    public function test_it_stores_new_articles_from_the_feed(): void
    {
        Http::fake(['example.com/*' => Http::response(self::FEED)]);
        $source = Source::factory()->create(['feed_url' => 'https://example.com/feed']);

        FetchSourceFeed::dispatchSync($source);

        $this->assertSame(2, $source->posts()->count());

        $post = $source->posts()->where('guid', 'first')->first();
        $this->assertSame('First article', $post->title);
        $this->assertSame('First excerpt.', $post->excerpt);
        $this->assertSame('https://example.com/first', $post->original_url);
        $this->assertSame('news', $post->category->slug);
        $this->assertSame('2026-11-02 08:30:00', $post->published_at->utc()->toDateTimeString());
        $this->assertNotNull($source->fresh()->last_fetched_at);
    }

    public function test_fetching_twice_does_not_create_duplicates(): void
    {
        Http::fake(['example.com/*' => Http::response(self::FEED)]);
        $source = Source::factory()->create(['feed_url' => 'https://example.com/feed']);

        FetchSourceFeed::dispatchSync($source);
        FetchSourceFeed::dispatchSync($source);

        $this->assertSame(2, Post::count());
    }

    public function test_articles_from_analysis_sources_go_to_the_analysis_category(): void
    {
        Http::fake(['example.com/*' => Http::response(self::FEED)]);
        $source = Source::factory()->create([
            'type' => SourceType::Analysis,
            'feed_url' => 'https://example.com/feed',
        ]);

        FetchSourceFeed::dispatchSync($source);

        $this->assertSame(['analysis'], $source->posts()->with('category')->get()->pluck('category.slug')->unique()->values()->all());
    }

    public function test_inactive_sources_are_skipped(): void
    {
        Http::fake();
        $source = Source::factory()->create(['is_active' => false]);

        FetchSourceFeed::dispatchSync($source);

        Http::assertNothingSent();
        $this->assertSame(0, Post::count());
    }

    public function test_a_failing_feed_throws_so_the_job_is_retried(): void
    {
        Http::fake(['example.com/*' => Http::response('Server error', 500)]);
        $source = Source::factory()->create(['feed_url' => 'https://example.com/feed']);

        $this->expectException(RequestException::class);

        FetchSourceFeed::dispatchSync($source);
    }

    public function test_the_command_queues_one_job_per_fetchable_source(): void
    {
        Queue::fake();
        Source::factory()->count(2)->create();
        Source::factory()->withoutFeed()->create();
        Source::factory()->create(['is_active' => false]);

        $this->artisan('feeds:fetch')->assertSuccessful();

        Queue::assertPushed(FetchSourceFeed::class, 2);
    }

    public function test_one_failing_feed_does_not_stop_the_others(): void
    {
        Http::fake([
            'broken.example.com/*' => Http::response('Blocked', 403),
            'example.com/*' => Http::response(self::FEED),
        ]);
        Source::factory()->create(['name' => 'Broken', 'feed_url' => 'https://broken.example.com/feed']);
        $working = Source::factory()->create(['name' => 'Working', 'feed_url' => 'https://example.com/feed']);

        $this->artisan('feeds:fetch', ['--sync' => true])
            ->expectsOutputToContain('Could not fetch Broken')
            ->expectsOutputToContain('Fetched Working')
            ->assertFailed();

        $this->assertSame(2, $working->posts()->count());
    }

    public function test_the_command_can_fetch_a_single_source(): void
    {
        Queue::fake();
        Source::factory()->create(['slug' => 'nk-news']);
        Source::factory()->create(['slug' => 'daily-nk']);

        $this->artisan('feeds:fetch', ['source' => 'nk-news'])->assertSuccessful();

        Queue::assertPushed(FetchSourceFeed::class, fn (FetchSourceFeed $job) => $job->source->slug === 'nk-news');
        Queue::assertPushed(FetchSourceFeed::class, 1);
    }
}
