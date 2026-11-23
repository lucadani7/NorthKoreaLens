<?php

namespace Tests\Feature;

use App\Data\PageData;
use App\Data\TeamMemberData;
use App\Enums\SourceType;
use App\Models\Source;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The phone menu shares the feed sections on every public page.
        $this->seed(CategorySeeder::class);
    }

    /** @return array<string, array{string, string}> */
    public static function pages(): array
    {
        return [
            'about' => ['about', 'About NK Lens'],
            'editorial policy' => ['editorial-policy', 'Editorial policy'],
            'how to help' => ['how-to-help', 'How you can help'],
        ];
    }

    #[DataProvider('pages')]
    public function test_each_page_is_public(string $route, string $title): void
    {
        $this->get(route($route))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('page')
                ->where('page.title', $title)
                ->where('page.locale', 'en')
                ->whereType('page.description', 'string')
                ->where('page.html', fn (string $html) => str_contains($html, '<h2>'))
            );
    }

    public function test_the_team_page_shows_a_card_for_each_member(): void
    {
        $this->withCookie('locale', 'ro')
            ->get(route('team'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('team')
                ->where('page.title', 'Echipa')
                ->has('members', count(config('team.members')))
                ->where('members.0.name', 'Luca Daniel Ionescu')
                ->where('members.0.country', 'România')
                ->whereType('members.0.profession', 'string')
                ->whereType('members.0.roles', 'string')
                // Notes can link elsewhere; such links open in a new tab.
                ->where('members.0.notesHtml', fn (string $html) => str_contains($html, 'href="https://www.youtube.com/@haihuin2"')
                    && str_contains($html, 'target="_blank"')
                    && ! str_contains($html, '<p>'))
            );
    }

    public function test_every_team_member_is_described_in_every_language(): void
    {
        /** @var list<string> $locales */
        $locales = array_keys(config('locales.supported'));

        foreach ($locales as $locale) {
            app()->setLocale($locale);

            foreach (TeamMemberData::fromConfig() as $member) {
                $this->assertNotNull($member->country, "{$member->key} in {$locale}");
                $this->assertNotNull($member->roles, "{$member->key} in {$locale}");
            }
        }
    }

    public function test_pages_are_shown_in_the_visitor_language(): void
    {
        $this->withCookie('locale', 'ro')
            ->get(route('about'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('page.title', 'Despre NK Lens')
                ->where('page.locale', 'ro')
            );
    }

    public function test_a_page_falls_back_to_english_when_its_translation_is_missing(): void
    {
        $page = PageData::load('editorial-policy', 'ja');

        $this->assertSame('en', $page?->locale);
        $this->assertSame('Editorial policy', $page->title);
    }

    public function test_every_page_exists_in_every_interface_language(): void
    {
        foreach (['about', 'team', 'editorial-policy', 'how-to-help', 'sources'] as $slug) {
            $english = PageData::load($slug, 'en');
            $this->assertNotNull($english);

            foreach (array_keys(config('locales.supported')) as $locale) {
                $page = PageData::load($slug, $locale);

                $this->assertSame($locale, $page?->locale, "{$slug}/{$locale}.md is missing.");
                $this->assertNotSame('', $page->title, "{$slug}/{$locale}.md has no title.");
                // Same sections as the English page, so no part is left untranslated.
                $this->assertSame(
                    substr_count($english->html, '<h2>'),
                    substr_count($page->html, '<h2>'),
                    "{$slug}/{$locale}.md does not have the same sections as the English page.",
                );
            }
        }
    }

    public function test_links_to_other_sites_open_in_a_new_tab(): void
    {
        $html = PageData::load('team', 'en')?->html ?? '';

        // The attributes can come in any order, so look at the whole <a> tag.
        $this->assertSame(1, preg_match('/<a [^>]*href="https:\/\/github\.com\/lucadani7\/NorthKoreaLens"[^>]*>/', $html, $link));
        $this->assertStringContainsString('target="_blank"', $link[0]);
        $this->assertStringContainsString('rel="noopener noreferrer"', $link[0]);

        $this->assertStringContainsString('<a href="/how-to-help">', $html);
    }

    public function test_unknown_or_unsafe_page_names_are_not_loaded(): void
    {
        $this->assertNull(PageData::load('missing-page', 'en'));
        $this->assertNull(PageData::load('../../../.env', 'en'));
        $this->assertNull(PageData::load('about', '../en'));
    }

    public function test_the_sources_page_lists_active_sources(): void
    {
        $nkNews = Source::factory()->create(['name' => 'NK News', 'website_url' => 'https://www.nknews.org']);
        Source::factory()->create(['name' => 'Paused outlet', 'is_active' => false]);

        $this->get(route('sources'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('sources')
                ->where('page.title', 'Sources')
                ->has('sources', 1)
                ->where('sources.0.id', $nkNews->id)
                ->where('sources.0.websiteUrl', 'https://www.nknews.org')
            );
    }

    public function test_the_sources_page_never_links_to_state_media(): void
    {
        Source::factory()->stateMedia()->create([
            'name' => 'Rodong Sinmun',
            'website_url' => 'http://www.rodong.rep.kp',
        ]);

        $this->get(route('sources'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('sources.0.type', SourceType::StateMedia->value)
                ->where('sources.0.websiteUrl', null)
            )
            ->assertDontSee('rodong.rep.kp');
    }
}
