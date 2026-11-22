<?php

namespace App\Data;

use Illuminate\Support\Str;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use Spatie\LaravelData\Data;

/**
 * A page written by the team in Markdown: resources/content/pages/{slug}/{locale}.md.
 * The first line is the title ("# About NK Lens"); the rest is the body.
 */
class PageData extends Data
{
    public function __construct(
        public string $slug,
        public string $title,
        /** The first paragraph as plain text, for the meta description. */
        public ?string $description,
        /** The body rendered to HTML. Raw HTML in the Markdown is stripped. */
        public string $html,
        /** The language the page is written in: the visitor's, or English when there is no translation yet. */
        public string $locale,
    ) {}

    /** The page in the given language, falling back to English; null when the page does not exist. */
    public static function load(string $slug, string $locale): ?self
    {
        if (preg_match('/\A[a-z0-9-]+\z/', $slug.$locale) !== 1) {
            return null;
        }

        foreach (array_unique([$locale, 'en']) as $candidate) {
            $path = resource_path("content/pages/{$slug}/{$candidate}.md");

            if (is_file($path)) {
                return self::parse($slug, (string) file_get_contents($path), $candidate);
            }
        }

        return null;
    }

    private static function parse(string $slug, string $markdown, string $locale): self
    {
        $title = '';

        if (preg_match('/\A#\s+(.+)\R/u', $markdown, $match) === 1) {
            $title = trim($match[1]);
            $markdown = substr($markdown, strlen($match[0]));
        }

        $html = Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            // Links to other sites open in a new tab; links between our pages stay in the same one.
            'external_link' => [
                'internal_hosts' => [(string) parse_url((string) config('app.url'), PHP_URL_HOST)],
                'open_in_new_window' => true,
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
        ], [new ExternalLinkExtension]);

        $description = null;

        if (preg_match('/<p>(.*?)<\/p>/su', $html, $match) === 1) {
            $description = Str::limit(trim(strip_tags($match[1])), 160);
        }

        return new self(
            slug: $slug,
            title: $title,
            description: $description,
            html: $html,
            locale: $locale,
        );
    }
}
