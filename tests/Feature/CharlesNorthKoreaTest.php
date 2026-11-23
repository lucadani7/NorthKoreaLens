<?php

namespace Tests\Feature;

use App\Enums\SourceType;
use App\Jobs\FetchSourceFeed;
use App\Models\Source;
use App\Support\YouTube;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CharlesNorthKoreaTest extends TestCase
{
    use RefreshDatabase;

    private const FEED = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <feed xmlns:yt="http://www.youtube.com/xml/schemas/2015" xmlns:media="http://search.yahoo.com/mrss/" xmlns="http://www.w3.org/2005/Atom">
          <title>Charles North Korea</title>
          <entry>
            <id>yt:video:AAAAAAAAAAA</id>
            <title>How I escaped North Korea twice</title>
            <link rel="alternate" href="https://www.youtube.com/watch?v=AAAAAAAAAAA"/>
            <published>2026-11-20T09:00:00+00:00</published>
            <media:group><media:description>My story.</media:description></media:group>
          </entry>
          <entry>
            <id>yt:video:BBBBBBBBBBB</id>
            <title>North Korean tries pizza</title>
            <link rel="alternate" href="https://www.youtube.com/shorts/BBBBBBBBBBB"/>
            <published>2026-11-21T09:00:00+00:00</published>
            <media:group><media:description>#shorts</media:description></media:group>
          </entry>
        </feed>
        XML;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CategorySeeder::class);
    }

    private function charles(): Source
    {
        return Source::factory()->create([
            'name' => 'Charles North Korea (Charles Ryu)',
            'slug' => 'charles-north-korea',
            'type' => SourceType::Testimony,
            'feed_url' => 'https://www.youtube.com/feeds/videos.xml?channel_id=UCv_zJYPJndI4JH4P_p-eY5w',
            'keywords' => null,
            'needs_review' => true,
            'skip_shorts' => true,
        ]);
    }

    public function test_shorts_links_are_recognised(): void
    {
        $this->assertTrue(YouTube::isShort('https://www.youtube.com/shorts/BBBBBBBBBBB'));
        $this->assertFalse(YouTube::isShort('https://www.youtube.com/watch?v=AAAAAAAAAAA'));
        $this->assertFalse(YouTube::isShort(null));
    }

    public function test_only_regular_videos_are_imported_as_drafts_in_human_rights(): void
    {
        Http::fake(['www.youtube.com/*' => Http::response(self::FEED)]);
        $source = $this->charles();

        FetchSourceFeed::dispatchSync($source);

        $this->assertSame(['yt:video:AAAAAAAAAAA'], $source->posts()->pluck('guid')->all());
        $post = $source->posts()->firstOrFail();
        $this->assertSame('human-rights', $post->category->slug);
        $this->assertNull($post->published_at);
    }

    public function test_shorts_are_kept_for_sources_that_do_not_skip_them(): void
    {
        Http::fake(['www.youtube.com/*' => Http::response(self::FEED)]);
        $source = $this->charles();
        $source->update(['skip_shorts' => false]);

        FetchSourceFeed::dispatchSync($source);

        $this->assertSame(2, $source->posts()->count());
    }

    public function test_readers_see_the_source_notice_in_their_language(): void
    {
        Http::fake(['www.youtube.com/*' => Http::response(self::FEED)]);
        $source = $this->charles();
        FetchSourceFeed::dispatchSync($source);
        $post = $source->posts()->firstOrFail();
        $post->publish();

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.source.notice', fn (string $notice) => str_contains($notice, 'innocent until proven guilty'))
            );

        app()->setLocale('ro');
        $this->assertStringContainsString('nevinovat până la proba contrarie', (string) $source->notice());
    }

    public function test_sources_without_a_notice_have_none(): void
    {
        $this->assertNull(Source::factory()->create(['slug' => 'nk-news'])->notice());
    }

    public function test_live_broadcasts_are_recognised_by_their_title(): void
    {
        foreach ([
            'Welcome to our live show!',
            'Rubberducking Livestream | Season 3 Ep. 3',
            'Rubber Ducking Podcast Nov. 15 Episode | LIVE',
            'Rubber Ducking Podcast Special Episode LIVE!',
            'Q&A live stream with a defector',
        ] as $title) {
            $this->assertTrue(YouTube::isLivestreamTitle($title), $title);
        }

        foreach ([
            'North Korea says it launched rockets at live-fire demo',
            'Where do North Korean elites live?',
            'Life of a North Korean Special Forces Soldier',
            'OLIVER visits Pyongyang',
        ] as $title) {
            $this->assertFalse(YouTube::isLivestreamTitle($title), $title);
        }
    }

    public function test_live_broadcasts_are_not_imported(): void
    {
        Http::fake(['www.youtube.com/*' => Http::response(str_replace(
            'How I escaped North Korea twice',
            'Welcome to our live show!',
            self::FEED,
        ))]);
        $source = $this->charles();

        FetchSourceFeed::dispatchSync($source);

        $this->assertSame(0, $source->posts()->count());
    }
}
