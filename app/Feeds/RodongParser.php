<?php

namespace App\Feeds;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Reads the headlines on the English home page of Rodong Sinmun (rodong.rep.kp/en/), which has
 * no RSS feed. Article links look like "index.php?MTJAMjAyNi0xMS0yMi0wMDFAMkAxQEAwQDFA==", the
 * base64 of "12@2026-11-22-001@2@1@@0@1@": an article, its id (date and number) and its section.
 *
 * Only the title, the date and the link are kept: no text, and no images (they are inlined as
 * base64 and come from the North Korean server).
 */
class RodongParser
{
    /** @return list<FeedItem> */
    public function parse(string $html, string $baseUrl): array
    {
        preg_match_all('/<a\s[^>]*href="index\.php\?([A-Za-z0-9+\/=]+)"[^>]*>(.*?)<\/a>/su', $html, $links, PREG_SET_ORDER);

        $items = [];

        foreach ($links as [, $query, $label]) {
            // Their padding is often wrong ("…QDFA==" is 38 characters), so decode without the "=".
            $decoded = base64_decode(rtrim($query, '='), true);

            if ($decoded === false || preg_match('/\A12@(\d{4}-\d{2}-\d{2})-(\d{3})@/', $decoded, $id) !== 1) {
                continue;
            }

            $title = $this->text($label);
            $guid = "rodong:{$id[1]}-{$id[2]}";

            // The same article is often linked twice: once from its photo, once from its title.
            if ($title === '' || isset($items[$guid])) {
                continue;
            }

            $items[$guid] = new FeedItem(
                guid: $guid,
                title: $title,
                link: rtrim($baseUrl, '/').'/index.php?'.$query,
                excerpt: null,
                imageUrl: null,
                publishedAt: new DateTimeImmutable($id[1], new DateTimeZone('Asia/Pyongyang')),
            );
        }

        return array_values($items);
    }

    private function text(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/[\s\x{00A0}]+/u', ' ', $text));
    }
}
