<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The German edition never uses "Führer" or "Anschluss" (see the editorial
 * policy). The only place allowed to name them is the policy page itself.
 */
class GermanWordingTest extends TestCase
{
    private const FORBIDDEN = '/f(ü|ue)hrer|anschlu(ss|ß)/iu';

    private const ALLOWED = ['resources/content/pages/editorial-policy/de.md'];

    /**
     * @return array<string, array{string}>
     */
    public static function germanTexts(): array
    {
        $root = dirname(__DIR__, 2);
        $texts = [];

        foreach (glob($root.'/database/data/*/*.json') as $file) {
            $posts = json_decode(file_get_contents($file), true)['posts'] ?? [];

            foreach ($posts as $post) {
                foreach ($post['de'] ?? [] as $field => $value) {
                    if (is_string($value)) {
                        $texts[self::relative($root, $file)." {$post['key']}.{$field}"] = [$value];
                    }
                }
            }
        }

        $files = [
            $root.'/lang/de.json',
            ...glob($root.'/lang/de/*.php'),
            ...glob($root.'/resources/content/pages/*/de.md'),
        ];

        foreach ($files as $file) {
            $relative = self::relative($root, $file);

            if (! in_array($relative, self::ALLOWED, true)) {
                $texts[$relative] = [file_get_contents($file)];
            }
        }

        return $texts;
    }

    #[DataProvider('germanTexts')]
    public function test_german_text_avoids_forbidden_words(string $text): void
    {
        preg_match_all(self::FORBIDDEN, $text, $matches);

        $this->assertSame([], $matches[0], 'Found a word the German edition avoids: '.implode(', ', $matches[0]));
    }

    private static function relative(string $root, string $file): string
    {
        return ltrim(substr($file, strlen($root)), '/');
    }
}
