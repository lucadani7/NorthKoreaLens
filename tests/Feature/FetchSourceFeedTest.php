<?php

namespace Tests\Feature;

use App\Contracts\SpeechSynthesizer;
use App\Enums\SourceType;
use App\Feeds\FeedImporter;
use App\Feeds\FeedItem;
use App\Jobs\FetchSourceFeed;
use App\Jobs\GeneratePostAudio;
use App\Models\Post;
use App\Models\Source;
use App\Services\PostAudioService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SourceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Tests\TestCase;

class FetchSourceFeedTest extends TestCase
{
    use RefreshDatabase;

    private const UHN_PLUS_FEED = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0">
          <channel>
            <item>
              <title>Corea del Norte anuncia nuevas medidas</title>
              <link>https://www.uhnplus.com/noticias/corea-del-norte-medidas/</link>
              <guid isPermaLink="false">uhnplus-north-korea-1</guid>
              <pubDate>Mon, 02 Nov 2026 08:30:00 +0000</pubDate>
              <description>Artículo de prueba sobre Corea del Norte.</description>
              <category>Corea del Norte</category>
            </item>
            <item>
              <title>Una noticia con un titular diferente</title>
              <link>https://www.uhnplus.com/noticias/otra-medida/</link>
              <guid isPermaLink="false">uhnplus-tagged-only</guid>
              <pubDate>Mon, 02 Nov 2026 09:30:00 +0000</pubDate>
              <description>El titular y el resumen no mencionan el tema.</description>
              <category><![CDATA[Corea del Norte]]></category>
            </item>
          </channel>
        </rss>
        XML;

    private const UHN_PLUS_ARCHIVE_PAGE_1 = <<<'HTML'
        <!doctype html>
        <html>
          <body>
            <script>var paginationAvailablePagesNumber = '2';</script>
            <h1 class="c-page-head__title">Corea del Norte</h1>
            <article class="c-post-card">
          <h3 class="c-post-card__title">
            <a class="c-post-card__link" href="/noticias/corea-del-norte-medidas/">Corea del Norte anuncia nuevas medidas</a>
          </h3>
          <time datetime="2026-11-02"></time>
            </article>
          </body>
        </html>
        HTML;

    private const UHN_PLUS_ARCHIVE_PAGE_2 = <<<'HTML'
        <!doctype html>
        <html>
          <body>
            <h1 class="c-page-head__title">Corea del Norte</h1>
            <article class="c-post-card">
          <h3 class="c-post-card__title">
            <a class="c-post-card__link" href="/noticias/medida-historica/">Una noticia histórica con otro titular</a>
          </h3>
          <div class="c-post-card__excerpt">El resumen tampoco menciona el tema.</div>
          <img class="c-post-card__image" src="https://images.example.com/article.jpg">
          <time datetime="2026-06-06"></time>
            </article>
          </body>
        </html>
        HTML;

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

    public function test_uhn_plus_articles_are_imported_as_external_spanish_news_without_audio_restrictions(): void
    {
        $this->seed(SourceSeeder::class);

        $feedUrl = 'https://www.uhnplus.com/tag/corea-del-norte/rss/';
        $source = Source::query()->where('slug', 'uhn-plus')->firstOrFail();

        $this->assertSame('UHN Plus', $source->name);
        $this->assertSame('https://www.uhnplus.com', $source->website_url);
        $this->assertSame($feedUrl, $source->feed_url);
        $this->assertSame('ghost', $source->feed_format);
        $this->assertSame('es', $source->language);
        $this->assertFalse($source->forbids_ai);

        Http::fake([
            $feedUrl => Http::response(self::UHN_PLUS_FEED),
            'https://www.uhnplus.com/tag/corea-del-norte/' => Http::response(self::UHN_PLUS_ARCHIVE_PAGE_1),
            'https://www.uhnplus.com/tag/corea-del-norte/page/2/' => Http::response(self::UHN_PLUS_ARCHIVE_PAGE_2),
        ]);

        FetchSourceFeed::dispatchSync($source);

        Http::assertSent(fn (Request $request): bool => $request->url() === $feedUrl);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://www.uhnplus.com/tag/corea-del-norte/');
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://www.uhnplus.com/tag/corea-del-norte/page/2/');

        $post = $source->posts()->where('guid', 'uhnplus-north-korea-1')->firstOrFail();

        $this->assertSame($source->id, $post->source_id);
        $this->assertFalse($post->is_nklens_original);
        $this->assertFalse($post->isOriginalByNkLens());
        $this->assertSame('es', $post->language);
        $this->assertSame('https://www.uhnplus.com/noticias/corea-del-norte-medidas/', $post->original_url);
        $this->assertSame('Corea del Norte anuncia nuevas medidas', $post->title);
        $this->assertSame('Artículo de prueba sobre Corea del Norte.', $post->excerpt);
        $this->assertSame('Corea del Norte', $post->search_terms);
        $this->assertSame('news', $post->category->slug);

        $taggedOnlyPost = $source->posts()->where('guid', 'uhnplus-tagged-only')->firstOrFail();
        $this->assertSame('Corea del Norte', $taggedOnlyPost->search_terms);
        $historicalPost = $source->posts()->where('original_url', 'https://www.uhnplus.com/noticias/medida-historica/')->firstOrFail();
        $this->assertSame('Corea del Norte', $historicalPost->search_terms);

        config(['scout.driver' => 'database']);
        $this->get(route('search', ['q' => 'Corea del Norte', 'language' => 'es']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts', 3)
                ->where('posts', fn ($posts) => collect($posts)
                    ->pluck('id')
                    ->sort()
                    ->values()
                    ->all() === collect([$post->id, $taggedOnlyPost->id, $historicalPost->id])
                    ->sort()
                    ->values()
                    ->all())
                ->where('pagination.total', 3)
            );

        $synthesizer = Mockery::mock(SpeechSynthesizer::class);
        $synthesizer->shouldNotReceive('synthesize');

        (new GeneratePostAudio((int) $post->getKey(), 'es'))
            ->handle(new PostAudioService($synthesizer));

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.title', 'Corea del Norte anuncia nuevas medidas')
                ->where('post.contentLocale', 'es')
                ->where('post.originalUrl', $post->original_url)
                ->where('post.source.name', 'UHN Plus')
                ->where('post.source.websiteUrl', 'https://www.uhnplus.com')
                ->where('post.source.forbidsAi', false)
                ->where('audioUrl', null)
            );
    }

    public function test_refetching_a_feed_updates_search_terms_on_existing_posts(): void
    {
        $source = Source::factory()->create(['language' => 'es']);
        $importer = new FeedImporter;
        $item = new FeedItem(
            guid: 'existing-uhn-item',
            title: 'Una noticia con un titular diferente',
            link: 'https://www.uhnplus.com/noticias/otra-medida/',
            excerpt: 'El titular y el resumen no mencionan el tema.',
            imageUrl: null,
            publishedAt: null,
            description: 'El titular y el resumen no mencionan el tema.',
        );
        $taggedItem = new FeedItem(
            guid: 'different-guid-from-archive',
            title: 'Una noticia con un titular diferente',
            link: 'https://www.uhnplus.com/noticias/otra-medida/',
            excerpt: 'El titular y el resumen no mencionan el tema.',
            imageUrl: null,
            publishedAt: null,
            description: 'El titular y el resumen no mencionan el tema.',
            categories: ['Corea del Norte'],
        );

        $this->assertTrue($importer->import($source, $item));
        $this->assertFalse($importer->import($source, $taggedItem));
        $this->assertSame('Corea del Norte', $source->posts()->firstOrFail()->search_terms);
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
