<?php

namespace App\Feeds;

use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

class GhostArchiveParser
{
    private const MAX_PAGES = 100;

    public function archiveUrl(string $feedUrl): string
    {
        $parts = parse_url($feedUrl);
        $path = is_array($parts) ? ($parts['path'] ?? '') : '';

        if (! is_array($parts)
            || ! isset($parts['scheme'], $parts['host'])
            || preg_match('~/rss/?$~', $path) !== 1) {
            throw new InvalidFeedException('The Ghost feed URL does not end with /rss/.');
        }

        $archivePath = preg_replace('~/rss/?$~', '/', $path);
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return $parts['scheme'].'://'.$parts['host'].$port.$archivePath;
    }

    public function pageCount(string $html): int
    {
        if (preg_match('/paginationAvailablePagesNumber\s*=\s*[\'"](\d+)[\'"]/', $html, $match) !== 1) {
            throw new InvalidFeedException('The Ghost archive does not declare its page count.');
        }

        $pageCount = (int) $match[1];

        if ($pageCount < 1 || $pageCount > self::MAX_PAGES) {
            throw new InvalidFeedException('The Ghost archive page count is outside the supported range.');
        }

        return $pageCount;
    }

    /** @return list<FeedItem> */
    public function parse(string $html, string $archiveUrl): array
    {
        $previous = libxml_use_internal_errors(true);
        $loaded = false;

        try {
            $document = new DOMDocument;
            $loaded = $document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if (! $loaded) {
            throw new InvalidFeedException('The Ghost archive is not valid HTML.');
        }

        $xpath = new DOMXPath($document);
        $tagHeading = $this->firstElement(
            $xpath,
            '//h1[contains(concat(" ", normalize-space(@class), " "), " c-page-head__title ")]',
        );
        $tag = $tagHeading === null ? '' : $this->cleanText($tagHeading->textContent);

        if ($tag === '') {
            throw new InvalidFeedException('The Ghost archive tag heading is missing.');
        }

        $articles = $xpath->query('//article[contains(concat(" ", normalize-space(@class), " "), " c-post-card ")]');

        if ($articles === false) {
            throw new InvalidFeedException('Could not read article cards from the Ghost archive.');
        }

        $items = [];

        foreach ($articles as $article) {
            if (! $article instanceof DOMElement) {
                continue;
            }

            $linkNode = $this->firstElement(
                $xpath,
                './/a[contains(concat(" ", normalize-space(@class), " "), " c-post-card__link ")]',
                $article,
            );

            if (! $linkNode instanceof DOMElement) {
                continue;
            }

            $link = $this->articleUrl($linkNode->getAttribute('href'), $archiveUrl);
            $title = $this->cleanText($linkNode->textContent);

            if ($link === null || $title === '') {
                continue;
            }

            $excerptElement = $this->firstElement(
                $xpath,
                './/*[contains(concat(" ", normalize-space(@class), " "), " c-post-card__excerpt ")]',
                $article,
            );
            $image = $this->firstElement(
                $xpath,
                './/img[contains(concat(" ", normalize-space(@class), " "), " c-post-card__image ")]',
                $article,
            );
            $date = $this->firstElement($xpath, './/time[@datetime]', $article);
            $publishedAt = null;

            if ($date instanceof DOMElement && $date->getAttribute('datetime') !== '') {
                try {
                    $publishedAt = new DateTimeImmutable($date->getAttribute('datetime'));
                } catch (\Exception) {
                    $publishedAt = null;
                }
            }

            $imageUrl = $image !== null
                ? $this->absoluteUrl($image->getAttribute('src'), $archiveUrl)
                : null;

            $items[] = new FeedItem(
                guid: 'ghost:'.hash('sha256', $link),
                title: $title,
                link: $link,
                excerpt: $excerptElement === null ? null : $this->cleanText($excerptElement->textContent),
                imageUrl: $imageUrl,
                publishedAt: $publishedAt,
                description: $excerptElement === null ? '' : $this->cleanText($excerptElement->textContent),
                categories: [$tag],
            );
        }

        return $items;
    }

    private function firstElement(DOMXPath $xpath, string $expression, ?DOMNode $context = null): ?DOMElement
    {
        $nodes = $xpath->query($expression, $context);

        if ($nodes === false) {
            throw new InvalidFeedException('Could not query the Ghost archive document.');
        }

        $node = $nodes->item(0);

        return $node instanceof DOMElement ? $node : null;
    }

    private function articleUrl(string $href, string $archiveUrl): ?string
    {
        $url = $this->absoluteUrl($href, $archiveUrl);

        if ($url === null || parse_url($url, PHP_URL_HOST) !== parse_url($archiveUrl, PHP_URL_HOST)) {
            return null;
        }

        return $url;
    }

    private function absoluteUrl(string $url, string $baseUrl): ?string
    {
        $url = trim($url);

        if ($url === '' || str_starts_with($url, '//')) {
            return null;
        }

        if (parse_url($url, PHP_URL_SCHEME) === null) {
            $base = parse_url($baseUrl);

            if (! is_array($base) || ! isset($base['scheme'], $base['host'])) {
                return null;
            }

            $port = isset($base['port']) ? ':'.$base['port'] : '';
            $url = $base['scheme'].'://'.$base['host'].$port.'/'.ltrim($url, '/');
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) && filter_var($url, FILTER_VALIDATE_URL) !== false
            ? $url
            : null;
    }

    private function cleanText(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    }
}
