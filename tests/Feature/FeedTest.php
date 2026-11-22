<?php

namespace Tests\Feature;

use App\Http\Controllers\FeedController;
use App\Models\Category;
use App\Models\Post;
use App\Models\Source;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CategorySeeder::class);
    }

    private function category(string $slug): Category
    {
        return Category::where('slug', $slug)->firstOrFail();
    }

    public function test_every_page_shares_the_sections_for_the_phone_menu(): void
    {
        $post = Post::factory()->for($this->category('analysis'))->create();

        $this->get(route('posts.show', $post))
            ->assertInertia(fn (Assert $page) => $page
                ->component('post')
                ->has('sections', 7)
                ->where('sections.0.slug', 'news')
            );
    }

    public function test_guests_can_see_the_feed(): void
    {
        $post = Post::factory()->for($this->category('news'))->create(['title' => 'Kim visits a shipyard']);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('feed')
                ->has('posts', 1)
                ->where('posts.0.id', $post->id)
                ->where('posts.0.title', 'Kim visits a shipyard')
                ->where('posts.0.isPropaganda', false)
                ->where('posts.0.category.slug', 'news')
                ->where('posts.0.source.name', $post->source->name)
                ->has('categories', 7)
                ->where('activeCategory', null)
            );
    }

    public function test_the_feed_is_paginated(): void
    {
        Post::factory()->count(FeedController::PER_PAGE + 5)->for($this->category('news'))->create();

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts', FeedController::PER_PAGE)
                ->where('pagination.currentPage', 1)
                ->where('pagination.lastPage', 2)
                ->where('pagination.total', FeedController::PER_PAGE + 5)
                ->where('pagination.previousPageUrl', null)
            );

        $this->get(route('home', ['page' => 2]))
            ->assertInertia(fn (Assert $page) => $page->has('posts', 5));
    }

    public function test_the_feed_can_be_filtered_by_category(): void
    {
        Post::factory()->for($this->category('news'))->create();
        $analysis = Post::factory()->for($this->category('analysis'))->create();

        $this->get(route('home', ['category' => 'analysis']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts', 1)
                ->where('posts.0.id', $analysis->id)
                ->where('activeCategory', 'analysis')
            );
    }

    public function test_the_propaganda_filter_includes_state_media_in_any_category(): void
    {
        Post::factory()->for($this->category('news'))->create();
        $stateMedia = Post::factory()
            ->for($this->category('news'))
            ->for(Source::factory()->stateMedia())
            ->create();

        $this->get(route('home', ['category' => 'propaganda']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts', 1)
                ->where('posts.0.id', $stateMedia->id)
                ->where('posts.0.isPropaganda', true)
                ->where('posts.0.source.type', 'state_media')
            );
    }

    public function test_unpublished_posts_are_hidden(): void
    {
        Post::factory()->unpublished()->for($this->category('news'))->create();

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->has('posts', 0));
    }

    public function test_an_unknown_category_returns_404(): void
    {
        $this->get(route('home', ['category' => 'does-not-exist']))->assertNotFound();
    }

    public function test_category_names_follow_the_visitor_language(): void
    {
        // The SetLocale middleware picks the language per request, so the test sends it like a browser would.
        $this->withCookie('locale', 'ro')
            ->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->where('categories.0.name', 'Știri'));
    }
}
