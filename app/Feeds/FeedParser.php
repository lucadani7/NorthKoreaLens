<?php

namespace App\Feeds;

use DateTimeImmutable;
use Exception;
use SimpleXMLElement;

/**
 * Reads RSS 2.0 and Atom feeds into FeedItem objects, with no third-party packages.
 * YouTube channel feeds are Atom with the description and thumbnail in <media:group>.
 */
class FeedParser
{
    /** Longest excerpt we keep from a third-party article. */
    public const EXCERPT_LENGTH = 300;

    private const NS_MEDIA = 'http://search.yahoo.com/mrss/';

    private const NS_CONTENT = 'http://purl.org/rss/1.0/modules/content/';

    private const NS_ATOM = 'http://www.w3.org/2005/Atom';

    /**
     * @return list<FeedItem>
     *
     * @throws InvalidFeedException
     */
    public function parse(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);

        try {
            // LIBXML_NONET: never let a feed make us load anything from the network.
            $root = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($root === false) {
            throw new InvalidFeedException('The feed is not valid XML.');
        }

        return match ($root->getName()) {
            'rss' => $this->parseRss($root),
            'feed' => $this->parseAtom($root),
            default => throw new InvalidFeedException("Unsupported feed format <{$root->getName()}>."),
        };
    }

    /** @return list<FeedItem> */
    private function parseRss(SimpleXMLElement $root): array
    {
        $items = [];

        foreach ($root->channel->item ?? [] as $item) {
            $link = $this->safeUrl((string) $item->link);
            $title = $this->cleanText((string) $item->title);

            if ($link === null || $title === '') {
                continue;
            }

            $content = (string) $item->children(self::NS_CONTENT)->encoded;
            $description = (string) $item->description;

            $items[] = new FeedItem(
                guid: trim((string) $item->guid) ?: $link,
                title: $title,
                link: $link,
                excerpt: $this->excerpt($description !== '' ? $description : $content),
                imageUrl: $this->rssImage($item, $content.$description),
                publishedAt: $this->date((string) $item->pubDate),
                description: $this->cleanText($description !== '' ? $description : $content),
                categories: $this->rssCategories($item),
            );
        }

        return $items;
    }

    /** @return list<FeedItem> */
    private function parseAtom(SimpleXMLElement $root): array
    {
        $items = [];
        $atom = $root->children(self::NS_ATOM);

        foreach ($atom->entry as $entry) {
            $link = $this->atomLink($entry);
            $title = $this->cleanText((string) $entry->title);

            if ($link === null || $title === '') {
                continue;
            }

            $summary = (string) ($entry->summary ?? '') ?: (string) ($entry->content ?? '');
            // children() of an element that is not there gives null, hence the ?-> all the way.
            $media = $entry->children(self::NS_MEDIA)->group?->children(self::NS_MEDIA);

            if ($summary === '') {
                $summary = (string) $media?->description;
            }

            $thumbnail = (string) $media?->thumbnail?->attributes()?->url;

            $items[] = new FeedItem(
                guid: trim((string) $entry->id) ?: $link,
                title: $title,
                link: $link,
                excerpt: $this->excerpt($summary),
                imageUrl: $this->firstImageIn($summary) ?? $this->safeUrl($thumbnail),
                publishedAt: $this->date((string) ($entry->published ?? '') ?: (string) $entry->updated),
                description: $this->cleanText($summary),
                categories: $this->atomCategories($entry),
            );
        }

        return $items;
    }

    /** @return list<string> */
    private function rssCategories(SimpleXMLElement $item): array
    {
        $categories = [];

        foreach ($item->category as $category) {
            $name = $this->cleanText((string) $category);

            if ($name !== '') {
                $categories[] = $name;
            }
        }

        return array_values(array_unique($categories));
    }

    /** @return list<string> */
    private function atomCategories(SimpleXMLElement $entry): array
    {
        $categories = [];

        foreach ($entry->children(self::NS_ATOM)->category as $category) {
            $term = (string) $category->attributes()->term;
            $name = $this->cleanText($term !== '' ? $term : (string) $category);

            if ($name !== '') {
                $categories[] = $name;
            }
        }

        return array_values(array_unique($categories));
    }

    private function atomLink(SimpleXMLElement $entry): ?string
    {
        foreach ($entry->link as $link) {
            // Atom attributes have no namespace, so they must be read through attributes().
            $attributes = $link->attributes();
            $rel = (string) $attributes->rel;

            if ($rel === '' || $rel === 'alternate') {
                return $this->safeUrl((string) $attributes->href);
            }
        }

        return null;
    }

    private function rssImage(SimpleXMLElement $item, string $html): ?string
    {
        $media = $item->children(self::NS_MEDIA);

        foreach ($media->content as $content) {
            $medium = (string) $content->attributes()->medium;
            $type = (string) $content->attributes()->type;

            if ($medium === 'image' || str_starts_with($type, 'image/') || ($medium === '' && $type === '')) {
                if ($url = $this->safeUrl((string) $content->attributes()->url)) {
                    return $url;
                }
            }
        }

        if ($url = $this->safeUrl((string) ($media->thumbnail?->attributes()->url ?? ''))) {
            return $url;
        }

        foreach ($item->enclosure as $enclosure) {
            if (str_starts_with((string) $enclosure->attributes()->type, 'image/')) {
                if ($url = $this->safeUrl((string) $enclosure->attributes()->url)) {
                    return $url;
                }
            }
        }

        return $this->firstImageIn($html);
    }

    private function firstImageIn(string $html): ?string
    {
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $match) !== 1) {
            return null;
        }

        return $this->safeUrl(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5));
    }

    /** Plain-text excerpt: no HTML, no extra whitespace, at most EXCERPT_LENGTH characters. */
    private function excerpt(string $html): ?string
    {
        $text = $this->cleanText($html);

        if ($text === '') {
            return null;
        }

        if (mb_strlen($text) <= self::EXCERPT_LENGTH) {
            return $text;
        }

        $cut = mb_substr($text, 0, self::EXCERPT_LENGTH);
        $lastSpace = mb_strrpos($cut, ' ');

        return rtrim($lastSpace !== false ? mb_substr($cut, 0, $lastSpace) : $cut, ' ,.;:').'…';
    }

    private function cleanText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    /** Only absolute http(s) URLs are kept, so a feed cannot inject javascript: or data: links. */
    private function safeUrl(string $url): ?string
    {
        $url = trim($url);
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (! in_array($scheme, ['http', 'https'], true) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return $url;
    }

    private function date(string $value): ?DateTimeImmutable
    {
        if (trim($value) === '') {
            return null;
        }

        try {
            return new DateTimeImmutable(trim($value));
        } catch (Exception) {
            return null;
        }
    }
}
