<?php

namespace Tests\Feature;

use App\Enums\SourceType;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['scout.driver' => 'database']);
    }

    public function test_search_returns_published_posts_with_source_labels_and_notices(): void
    {
        $category = Category::factory()->create();
        $yonhap = Source::factory()->create([
            'name' => 'KOREA NOW (Yonhap)',
            'forbids_ai' => true,
        ]);
        $yonhapPost = Post::factory()->for($category)->for($yonhap)->create([
            'title' => 'Archive search result',
        ]);

        $stateMedia = Source::factory()->stateMedia()->create([
            'name' => 'Rodong Sinmun',
        ]);
        $propagandaPost = Post::factory()->for($category)->for($stateMedia)->create([
            'title' => 'Another archive search result',
        ]);

        Post::factory()->for($category)->unpublished()->create([
            'title' => 'Draft archive search result',
        ]);

        $this->get(route('search', ['q' => 'archive search']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('search')
                ->where('query', 'archive search')
                ->has('posts', 2)
                ->where('pagination.total', 2)
                ->where('posts', function ($posts) use ($yonhapPost, $propagandaPost) {
                    $results = collect($posts);
                    $yonhap = $results->firstWhere('id', $yonhapPost->id);
                    $propaganda = $results->firstWhere('id', $propagandaPost->id);

                    return $yonhap['source']['forbidsAi'] === true
                        && $yonhap['source']['type'] === 'independent'
                        && $propaganda['isPropaganda'] === true
                        && $propaganda['source']['type'] === 'state_media';
                })
            );
    }

    public function test_an_empty_query_does_not_return_the_archive(): void
    {
        Post::factory()->create(['title' => 'Published archive result']);

        $this->get(route('search'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('search')
                ->where('query', '')
                ->has('posts', 0)
                ->where('pagination.total', 0)
            );
    }

    public function test_search_filters_combine_category_source_type_and_readable_language(): void
    {
        $category = Category::factory()->create();
        $source = Source::factory()->create(['type' => SourceType::Independent]);
        $matchingPost = Post::factory()->for($category)->for($source)->create([
            'title' => 'Distinctive filter combination',
            'language' => 'en',
        ]);
        PostTranslation::create([
            'post_id' => $matchingPost->id,
            'locale' => 'ro',
            'title' => 'Combinație de filtre distinctă',
        ]);

        Post::factory()->for(Category::factory()->create())->for($source)->create([
            'title' => 'Distinctive filter combination',
        ]);
        Post::factory()->for($category)->for(Source::factory()->stateMedia())->create([
            'title' => 'Distinctive filter combination',
        ]);

        $this->get(route('search', [
            'q' => 'distinctive filter',
            'category' => $category->id,
            'source_type' => SourceType::Independent->value,
            'language' => 'ro',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.category', (string) $category->id)
                ->where('filters.source_type', SourceType::Independent->value)
                ->where('filters.language', 'ro')
                ->has('posts', 1)
                ->where('posts.0.id', $matchingPost->id)
                ->where('pagination.total', 1)
            );
    }

    public function test_filter_only_search_uses_publication_dates_for_internal_and_external_posts(): void
    {
        $category = Category::factory()->create();
        $recentExternal = Post::factory()->for($category)->create([
            'title' => 'Distinctive publication period',
            'published_at' => now(),
            'source_published_at' => now()->subDays(3),
        ]);
        $oldExternal = Post::factory()->for($category)->create([
            'title' => 'Distinctive publication period',
            'published_at' => now()->subDays(2),
            'source_published_at' => now()->subDays(30),
        ]);
        $recentInternal = Post::factory()->ownContent()->for($category)->create([
            'title' => 'Distinctive publication period',
            'published_at' => now()->subDays(4),
        ]);

        $this->get(route('search', [
            'category' => $category->id,
            'period' => '7d',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('query', '')
                ->has('posts', 2)
                ->where('posts', fn ($posts) => collect($posts)
                    ->pluck('id')
                    ->sort()
                    ->values()
                    ->all() === collect([$recentExternal->id, $recentInternal->id])
                    ->sort()
                    ->values()
                    ->all())
                ->where('pagination.total', 2)
            );

        $this->get(route('search', [
            'category' => $category->id,
            'period' => 'custom',
            'from' => now()->subDays(7)->toDateString(),
            'to' => now()->toDateString(),
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts', 2)
                ->where('pagination.total', 2)
            );

        $this->assertDatabaseHas('posts', ['id' => $oldExternal->id]);
    }
}
