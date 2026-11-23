<?php

namespace Tests\Feature;

use App\Feeds\FeedImporter;
use App\Feeds\FeedItem;
use App\Feeds\FeedParser;
use App\Jobs\FetchSourceFeed;
use App\Models\Category;
use App\Models\Post;
use App\Models\Source;
use App\Support\YouTube;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class KoreaNowVideosTest extends TestCase
{
    use RefreshDatabase;

    /** Shaped like https://www.youtube.com/feeds/videos.xml?channel_id=… */
    private const YOUTUBE_FEED = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <feed xmlns:yt="http://www.youtube.com/xml/schemas/2015" xmlns:media="http://search.yahoo.com/mrss/" xmlns="http://www.w3.org/2005/Atom">
          <title>KOREA NOW</title>
          <entry>
            <id>yt:video:AAAAAAAAAAA</id>
            <yt:videoId>AAAAAAAAAAA</yt:videoId>
            <title>N. Korea fires ballistic missile into East Sea</title>
            <link rel="alternate" href="https://www.youtube.com/watch?v=AAAAAAAAAAA"/>
            <published>2026-11-20T09:00:00+00:00</published>
            <media:group>
              <media:title>N. Korea fires ballistic missile into East Sea</media:title>
              <media:thumbnail url="https://i1.ytimg.com/vi/AAAAAAAAAAA/hqdefault.jpg" width="480" height="360"/>
              <media:description>The launch came hours after the allies began drills.</media:description>
            </media:group>
          </entry>
          <entry>
            <id>yt:video:BBBBBBBBBBB</id>
            <yt:videoId>BBBBBBBBBBB</yt:videoId>
            <title>K-pop group tops the charts</title>
            <link rel="alternate" href="https://www.youtube.com/watch?v=BBBBBBBBBBB"/>
            <published>2026-11-21T09:00:00+00:00</published>
            <media:group>
              <media:description>A new record for the band in Seoul.</media:description>
            </media:group>
          </entry>
          <entry>
            <id>yt:video:CCCCCCCCCCC</id>
            <yt:videoId>CCCCCCCCCCC</yt:videoId>
            <title>Seoul weekly briefing</title>
            <link rel="alternate" href="https://www.youtube.com/shorts/CCCCCCCCCCC"/>
            <published>2026-11-22T09:00:00+00:00</published>
            <media:group>
              <media:description>Also: what the North Korean leader said on Friday.</media:description>
            </media:group>
          </entry>
        </feed>
        XML;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CategorySeeder::class);
    }

    private function koreaNow(): Source
    {
        return Source::factory()->create([
            'name' => 'KOREA NOW (Yonhap)',
            'feed_url' => 'https://www.youtube.com/feeds/videos.xml?channel_id=UCR-L8oZkHzgDh1ahJhH2WDQ',
            'category_id' => Category::where('slug', Category::KOREA_NOW)->value('id'),
            'keywords' => ['North Korea', 'N. Korea'],
            'needs_review' => true,
            'forbids_ai' => true,
        ]);
    }

    public function test_youtube_feeds_give_the_description_and_thumbnail(): void
    {
        $items = (new FeedParser)->parse(self::YOUTUBE_FEED);

        $this->assertCount(3, $items);
        $this->assertSame('yt:video:AAAAAAAAAAA', $items[0]->guid);
        $this->assertSame('https://www.youtube.com/watch?v=AAAAAAAAAAA', $items[0]->link);
        $this->assertSame('The launch came hours after the allies began drills.', $items[0]->excerpt);
        $this->assertSame('https://i1.ytimg.com/vi/AAAAAAAAAAA/hqdefault.jpg', $items[0]->imageUrl);
    }

    public function test_only_videos_about_north_korea_are_imported_as_drafts_in_their_section(): void
    {
        Http::fake(['www.youtube.com/*' => Http::response(self::YOUTUBE_FEED)]);
        $source = $this->koreaNow();

        FetchSourceFeed::dispatchSync($source);

        // The K-pop video is skipped; the briefing mentions "North Korean" in its description.
        $this->assertEqualsCanonicalizing(
            ['yt:video:AAAAAAAAAAA', 'yt:video:CCCCCCCCCCC'],
            $source->posts()->pluck('guid')->all(),
        );

        foreach ($source->posts as $post) {
            $this->assertSame(Category::KOREA_NOW, $post->category->slug);
            $this->assertNull($post->published_at);
            $this->assertNotNull($post->source_published_at);
        }
    }

    public function test_drafts_are_published_with_the_date_of_the_video(): void
    {
        Http::fake(['www.youtube.com/*' => Http::response(self::YOUTUBE_FEED)]);
        $source = $this->koreaNow();
        FetchSourceFeed::dispatchSync($source);

        $post = $source->posts()->where('guid', 'yt:video:AAAAAAAAAAA')->firstOrFail();
        $this->get(route('posts.show', $post))->assertNotFound();

        $post->publish();

        $this->assertSame('2026-11-20 09:00:00', $post->fresh()?->published_at?->toDateTimeString());
        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.youtubeId', 'AAAAAAAAAAA')
                // Readers are told that Yonhap forbids AI tools on its videos.
                ->where('post.source.forbidsAi', true)
                ->where('post.category.slug', Category::KOREA_NOW)
            );
    }

    public function test_a_rejected_draft_is_not_imported_again(): void
    {
        Http::fake(['www.youtube.com/*' => Http::response(self::YOUTUBE_FEED)]);
        $source = $this->koreaNow();
        FetchSourceFeed::dispatchSync($source);

        $source->posts()->where('guid', 'yt:video:AAAAAAAAAAA')->firstOrFail()->delete();
        FetchSourceFeed::dispatchSync($source);

        $this->assertSame(1, $source->posts()->count());
        $this->assertSame(2, Post::withTrashed()->where('source_id', $source->id)->count());
    }

    public function test_channel_notices_are_left_out_of_the_excerpt(): void
    {
        $item = fn (string $description) => new FeedItem(
            guid: 'yt:video:DDDDDDDDDDD',
            title: 'N. Korea test',
            link: 'https://www.youtube.com/watch?v=DDDDDDDDDDD',
            excerpt: null,
            imageUrl: null,
            publishedAt: null,
            description: $description,
        );

        $this->assertNull(FeedImporter::excerpt($item(
            'You can watch this video at https://koreanow.com Copyright(C) Unauthorized use, distribution, and employment of AI-based tools are prohibited. #NorthKorea',
        )));
        $this->assertSame(
            'North Korea fired a ballistic missile on Friday, the JCS said.',
            FeedImporter::excerpt($item('North Korea fired a ballistic missile on Friday, the JCS said. You can watch this video at https://koreanow.com Copyright(C) Yonhap #NK')),
        );
        // "copyright" in the news itself stays.
        $this->assertSame(
            'A court rules on a copyright case, and Pyongyang reacts.',
            FeedImporter::excerpt($item('A court rules on a copyright case, and Pyongyang reacts.')),
        );
    }

    public function test_sources_without_keywords_import_everything(): void
    {
        $source = Source::factory()->make(['keywords' => null]);

        $this->assertTrue($source->wants('K-pop group tops the charts'));
    }

    public function test_keywords_match_at_the_start_of_a_word_ignoring_case(): void
    {
        $source = Source::factory()->make(['keywords' => ['DPRK', 'North Korea', '북한']]);

        $this->assertTrue($source->wants('Sanctions on the dprk tighten'));
        $this->assertTrue($source->wants('North Korean soldiers at the border'));
        $this->assertTrue($source->wants('북한 미사일 발사'));
        $this->assertFalse($source->wants('ADPRKX is not a word we want'));
        $this->assertFalse($source->wants('South Korea wins the cup'));
    }

    public function test_youtube_video_ids_are_read_from_every_kind_of_link(): void
    {
        $this->assertSame('AAAAAAAAAAA', YouTube::videoId('https://www.youtube.com/watch?v=AAAAAAAAAAA&t=10s'));
        $this->assertSame('AAAAAAAAAAA', YouTube::videoId('https://youtu.be/AAAAAAAAAAA'));
        $this->assertSame('AAAAAAAAAAA', YouTube::videoId('https://www.youtube.com/shorts/AAAAAAAAAAA'));
        $this->assertSame('AAAAAAAAAAA', YouTube::videoId('https://www.youtube.com/live/AAAAAAAAAAA?si=x'));
        $this->assertNull(YouTube::videoId('https://www.nknews.org/watch?v=AAAAAAAAAAA'));
        $this->assertNull(YouTube::videoId('https://www.youtube.com/watch?v=bad"id'));
        $this->assertNull(YouTube::videoId(null));
    }

    public function test_the_section_has_a_name_in_every_language(): void
    {
        foreach (array_keys(config('locales.supported')) as $locale) {
            app()->setLocale($locale);

            $this->assertNotSame('categories.korea-now', Category::where('slug', Category::KOREA_NOW)->firstOrFail()->name(), $locale);
        }
    }
}
