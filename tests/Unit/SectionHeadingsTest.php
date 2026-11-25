<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A body paragraph that starts with "## " is shown as a section heading.
 * Every language of a post has the same sections, and a heading is always
 * a single line followed by text.
 */
class SectionHeadingsTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function posts(): array
    {
        $root = dirname(__DIR__, 2);
        $posts = [];

        foreach (glob($root.'/database/data/*/*.json') as $file) {
            foreach (json_decode(file_get_contents($file), true)['posts'] ?? [] as $post) {
                $posts[basename(dirname($file)).'/'.basename($file).' '.$post['key']] = [$post];
            }
        }

        return $posts;
    }

    /**
     * @param  array<string, mixed>  $post
     */
    #[DataProvider('posts')]
    public function test_every_language_has_the_same_sections(array $post): void
    {
        $counts = [];

        foreach ($post as $locale => $fields) {
            if (! is_array($fields) || ! isset($fields['body'])) {
                continue;
            }

            $paragraphs = preg_split('/\n{2,}/', trim($fields['body']));
            $headings = array_keys(array_filter($paragraphs, fn ($p) => str_starts_with($p, '## ')));

            foreach ($headings as $index) {
                $this->assertStringNotContainsString("\n", $paragraphs[$index], "{$locale}: a heading must be one line");
                $this->assertArrayHasKey($index + 1, $paragraphs, "{$locale}: a heading must be followed by text");
                $this->assertFalse(str_starts_with($paragraphs[$index + 1], '## '), "{$locale}: two headings in a row");
            }

            $this->assertFalse(str_starts_with($fields['excerpt'] ?? '', '## '), "{$locale}: the excerpt is not a heading");

            $counts[$locale] = count($headings);
        }

        $this->assertCount(1, array_unique($counts), 'Section counts differ: '.json_encode($counts));
    }
}
