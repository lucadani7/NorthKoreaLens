<?php

namespace Tests\Feature;

use App\Models\User;
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

    public function test_norwegian_browsers_get_bokmal(): void
    {
        foreach (['nb-NO,nb;q=0.9', 'no,en;q=0.8', 'nn-NO,nn;q=0.9,en;q=0.5'] as $header) {
            $this->withHeader('Accept-Language', $header)
                ->get(route('home'))
                ->assertInertia(fn (Assert $page) => $page
                    ->where('locale', 'nb')
                    ->where('translations.Sections', 'Seksjoner')
                );
        }
    }

    public function test_the_first_supported_browser_language_wins(): void
    {
        $this->withHeader('Accept-Language', 'ja-JP,ja;q=0.9,de;q=0.8,en;q=0.7')
            ->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'de'));
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

    public function test_settings_pages_are_shown_in_the_visitor_language(): void
    {
        $this->actingAs(User::factory()->create())
            ->withCookie('locale', 'ro')
            ->get(route('profile.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/profile')
                ->where('locale', 'ro')
                ->where('translations.Settings', 'Setări')
                ->where('translations.Delete account', 'Șterge contul')
            );
    }

    public function test_password_change_errors_are_translated(): void
    {
        $this->actingAs(User::factory()->create())
            ->withCookie('locale', 'ro')
            ->from(route('security.edit'))
            ->put(route('user-password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'a-new-long-password',
                'password_confirmation' => 'a-new-long-password',
            ])
            ->assertSessionHasErrors(['current_password' => 'Parola este greșită.']);
    }

    /** Every literal passed to t('…') in the React code, plus the strings the server translates for it. */
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

            // Page layouts, navigation items and breadcrumbs carry English titles that are translated where they are shown.
            preg_match_all("/\\b(?:title|description):\\s*'((?:[^'\\\\]|\\\\.)*)'/", $source, $matches);
            array_push($keys, ...array_map(fn ($key) => stripslashes($key), $matches[1]));
        }

        // Messages the server sends to the interface, such as toasts.
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path()));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            preg_match_all("/\\b__\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/", file_get_contents($file->getPathname()), $matches);
            array_push($keys, ...array_map(fn ($key) => stripslashes($key), $matches[1]));
        }

        return array_values(array_unique($keys));
    }
}
