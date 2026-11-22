<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_english_is_the_default(): void
    {
        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'en')
                ->has('locales', count(config('locales.supported')))
                ->where('locales.0', ['code' => 'en', 'name' => 'English'])
            );
    }

    public function test_the_browser_language_is_used_when_supported(): void
    {
        $this->withHeader('Accept-Language', 'ro-RO,ro;q=0.9,en;q=0.8')
            ->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'ro')
                ->where('translations.Sections', 'Rubrici')
            );
    }

    public function test_an_unsupported_browser_language_falls_back_to_english(): void
    {
        $this->withHeader('Accept-Language', 'ja-JP,ja;q=0.9')
            ->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));
    }

    public function test_the_saved_choice_wins_over_the_browser_language(): void
    {
        $this->withHeader('Accept-Language', 'ro-RO')
            ->withCookie('locale', 'ko')
            ->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'ko')
                ->where('translations.Sections', '섹션')
            );
    }

    public function test_visitors_can_pick_a_language(): void
    {
        $this->from(route('home'))
            ->post(route('locale.update'), ['locale' => 'de'])
            ->assertRedirect(route('home'))
            ->assertCookie('locale', 'de');
    }

    public function test_unknown_languages_are_rejected(): void
    {
        $this->from(route('home'))
            ->post(route('locale.update'), ['locale' => 'xx'])
            ->assertSessionHasErrors('locale')
            ->assertCookieMissing('locale');
    }

    public function test_every_language_translates_every_interface_string(): void
    {
        $keys = $this->keysUsedByTheInterface();
        $this->assertNotEmpty($keys);

        foreach (array_keys(config('locales.supported')) as $locale) {
            if ($locale === 'en') {
                continue;
            }

            $translations = json_decode(file_get_contents(lang_path("{$locale}.json")), true, flags: JSON_THROW_ON_ERROR);
            $missing = array_diff($keys, array_keys($translations));

            $this->assertSame([], array_values($missing), "lang/{$locale}.json is missing keys.");

            foreach (['categories', 'auth', 'passwords', 'validation'] as $group) {
                $this->assertFileExists(lang_path("{$locale}/{$group}.php"));
            }
        }
    }

    public function test_login_errors_are_shown_in_the_visitor_language(): void
    {
        $this->withCookie('locale', 'ro')
            ->post('/login', ['email' => 'nobody@example.com', 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => 'Datele de autentificare nu sunt corecte.']);
    }

    public function test_validation_messages_are_translated(): void
    {
        $this->withCookie('locale', 'de')
            ->post('/register', ['name' => '', 'email' => 'not-an-email', 'password' => 'x', 'password_confirmation' => 'y'])
            ->assertSessionHasErrors([
                'name' => 'Das Feld Name ist erforderlich.',
                'email' => 'Das Feld E-Mail muss eine gültige E-Mail-Adresse sein.',
            ]);
    }

    /** Every literal passed to t('…') in the React code. */
    private function keysUsedByTheInterface(): array
    {
        $keys = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('js')));

        foreach ($files as $file) {
            if (! preg_match('/\.tsx?$/', $file->getFilename())) {
                continue;
            }

            $source = file_get_contents($file->getPathname());

            preg_match_all("/\\bt\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/", $source, $matches);
            array_push($keys, ...array_map(fn ($key) => stripslashes($key), $matches[1]));

            // Auth pages pass an English title and description to their layout, which translates them.
            if (str_contains($file->getPathname(), 'pages'.DIRECTORY_SEPARATOR.'auth')) {
                preg_match_all("/\\b(?:title|description):\\s*'((?:[^'\\\\]|\\\\.)*)'/", $source, $matches);
                array_push($keys, ...array_map(fn ($key) => stripslashes($key), $matches[1]));
            }
        }

        return array_values(array_unique($keys));
    }
}
