<?php

namespace Tests\Feature;

use App\Data\PageData;
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
            'team' => ['team', 'The team'],
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

    public function test_pages_are_shown_in_the_visitor_language(): void
    {
        $this->withCookie('locale', 'ro')
            ->get(route('about'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('page.title', 'Despre NK Lens')
                ->where('page.locale', 'ro')
            );
    }

    public function test_pages_fall_back_to_english_when_not_translated_yet(): void
    {
        $this->withCookie('locale', 'de')
            ->get(route('editorial-policy'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'de')
                ->where('page.title', 'Editorial policy')
                ->where('page.locale', 'en')
            );
    }

    public function test_every_page_exists_in_english_and_romanian(): void
    {
        foreach (['about', 'team', 'editorial-policy', 'how-to-help', 'sources'] as $slug) {
            foreach (['en', 'ro'] as $locale) {
                $page = PageData::load($slug, $locale);

                $this->assertNotNull($page, "{$slug}/{$locale}.md is missing.");
                $this->assertSame($locale, $page->locale, "{$slug}/{$locale}.md is missing.");
                $this->assertNotSame('', $page->title, "{$slug}/{$locale}.md has no title.");
            }
        }
    }

    public function test_links_to_other_sites_open_in_a_new_tab(): void
    {
        $html = PageData::load('team', 'en')?->html ?? '';

        // The attributes can come in any order, so look at the whole <a> tag.
        $this->assertSame(1, preg_match('/<a [^>]*href="https:\/\/www\.youtube\.com\/@haihuin2"[^>]*>/', $html, $link));
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
