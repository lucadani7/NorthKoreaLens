<?php

namespace Tests\Unit;

use App\Feeds\FeedParser;
use App\Feeds\InvalidFeedException;
use PHPUnit\Framework\TestCase;

class FeedParserTest extends TestCase
{
    private const RSS = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/" xmlns:content="http://purl.org/rss/1.0/modules/content/">
          <channel>
            <title>Example news</title>
            <item>
              <title>Kim inspects a &amp;quot;new&amp;quot; shipyard</title>
              <link>https://example.com/shipyard</link>
              <guid isPermaLink="false">example-1</guid>
              <pubDate>Mon, 02 Nov 2026 08:30:00 +0000</pubDate>
              <description><![CDATA[<p>The leader <b>visited</b> the   shipyard.</p>]]></description>
              <media:content url="https://example.com/shipyard.jpg" medium="image" width="800" height="600"/>
            </item>
            <item>
              <title>Image only in the content</title>
              <link>https://example.com/content-image</link>
              <description>Short text.</description>
              <content:encoded><![CDATA[<p><img src="https://example.com/inline.jpg" alt=""> Body</p>]]></content:encoded>
            </item>
            <item>
              <title>Unsafe link</title>
              <link>javascript:alert(1)</link>
            </item>
            <item>
              <title></title>
              <link>https://example.com/no-title</link>
            </item>
          </channel>
        </rss>
        XML;

    private const ATOM = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <feed xmlns="http://www.w3.org/2005/Atom">
          <title>Example analysis</title>
          <entry>
            <id>tag:example.com,2026:1</id>
            <title>Satellite imagery of Yongbyon</title>
            <link rel="alternate" href="https://example.com/yongbyon"/>
            <link rel="enclosure" href="https://example.com/report.pdf"/>
            <published>2026-11-10T12:00:00Z</published>
            <summary>New construction at the site.</summary>
          </entry>
        </feed>
        XML;

    public function test_it_reads_rss_items(): void
    {
        $items = (new FeedParser)->parse(self::RSS);

        $this->assertCount(2, $items);

        $first = $items[0];
        $this->assertSame('example-1', $first->guid);
        $this->assertSame('Kim inspects a "new" shipyard', $first->title);
        $this->assertSame('https://example.com/shipyard', $first->link);
        $this->assertSame('The leader visited the shipyard.', $first->excerpt);
        $this->assertSame('https://example.com/shipyard.jpg', $first->imageUrl);
        $this->assertSame('2026-11-02T08:30:00+00:00', $first->publishedAt?->format(DATE_ATOM));
    }

    public function test_it_falls_back_to_the_link_as_guid_and_finds_inline_images(): void
    {
        $second = (new FeedParser)->parse(self::RSS)[1];

        $this->assertSame('https://example.com/content-image', $second->guid);
        $this->assertSame('https://example.com/inline.jpg', $second->imageUrl);
        $this->assertNull($second->publishedAt);
    }

    public function test_it_skips_items_with_unsafe_links_or_no_title(): void
    {
        $links = array_map(fn ($item) => $item->link, (new FeedParser)->parse(self::RSS));

        $this->assertNotContains('javascript:alert(1)', $links);
        $this->assertNotContains('https://example.com/no-title', $links);
    }

    public function test_it_reads_atom_entries(): void
    {
        $items = (new FeedParser)->parse(self::ATOM);

        $this->assertCount(1, $items);
        $this->assertSame('tag:example.com,2026:1', $items[0]->guid);
        $this->assertSame('https://example.com/yongbyon', $items[0]->link);
        $this->assertSame('New construction at the site.', $items[0]->excerpt);
        $this->assertSame('2026-11-10', $items[0]->publishedAt?->format('Y-m-d'));
    }

    public function test_it_reads_images_from_thumbnails_and_enclosures(): void
    {
        $xml = '<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/"><channel>'
            .'<item><title>Thumb</title><link>https://example.com/1</link>'
            .'<media:thumbnail url="https://example.com/thumb.jpg"/></item>'
            .'<item><title>Podcast</title><link>https://example.com/2</link>'
            .'<enclosure url="https://example.com/episode.mp3" type="audio/mpeg" length="1"/>'
            .'<enclosure url="https://example.com/cover.png" type="image/png" length="1"/></item>'
            .'</channel></rss>';

        $items = (new FeedParser)->parse($xml);

        $this->assertSame('https://example.com/thumb.jpg', $items[0]->imageUrl);
        $this->assertSame('https://example.com/cover.png', $items[1]->imageUrl);
    }

    public function test_it_shortens_long_excerpts(): void
    {
        $long = str_repeat('word ', 200);
        $xml = '<rss version="2.0"><channel><item><title>Long</title><link>https://example.com/long</link>'
            ."<description>{$long}</description></item></channel></rss>";

        $excerpt = (new FeedParser)->parse($xml)[0]->excerpt;

        $this->assertLessThanOrEqual(FeedParser::EXCERPT_LENGTH + 1, mb_strlen($excerpt));
        $this->assertStringEndsWith('…', $excerpt);
    }

    public function test_it_rejects_invalid_xml(): void
    {
        $this->expectException(InvalidFeedException::class);

        (new FeedParser)->parse('<html><body>Not a feed</body></html>');
    }

    public function test_it_rejects_broken_xml(): void
    {
        $this->expectException(InvalidFeedException::class);

        (new FeedParser)->parse('<rss><channel>');
    }
}
