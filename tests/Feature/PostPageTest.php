<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Source;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PostPageTest extends TestCase
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

    public function test_a_published_post_has_its_own_page(): void
    {
        $post = Post::factory()->for($this->category('news'))->create(['title' => 'Kim visits a shipyard']);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('post')
                ->where('post.id', $post->id)
                ->where('post.title', 'Kim visits a shipyard')
                ->where('body', null)
            );
    }

    public function test_team_posts_show_their_full_text(): void
    {
        $post = Post::factory()
            ->ownContent()
            ->for($this->category('curiosities'))
            ->create(['body' => "First paragraph.\n\nSecond paragraph."]);

        $this->get(route('posts.show', $post))
            ->assertInertia(fn (Assert $page) => $page
                ->where('body', "First paragraph.\n\nSecond paragraph.")
                ->where('post.hasBody', true)
                ->where('post.source', null)
            );
    }

    public function test_propaganda_shows_the_editorial_note(): void
    {
        $post = Post::factory()
            ->for($this->category('propaganda'))
            ->for(Source::factory()->stateMedia())
            ->create(['editorial_note' => 'Independent sources report food shortages in the region.']);

        $this->get(route('posts.show', $post))
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.isPropaganda', true)
                ->where('post.editorialNote', 'Independent sources report food shortages in the region.')
            );

        $this->get(route('home', ['category' => 'propaganda']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('posts.0.editorialNote', 'Independent sources report food shortages in the region.')
            );
    }

    public function test_drafts_are_not_public(): void
    {
        $post = Post::factory()->unpublished()->create();

        $this->get(route('posts.show', $post))->assertNotFound();
    }

    public function test_scheduled_posts_are_not_public_before_their_date(): void
    {
        $post = Post::factory()->create(['published_at' => now()->addDay()]);

        $this->get(route('posts.show', $post))->assertNotFound();
    }

    public function test_deleted_posts_are_gone(): void
    {
        $post = Post::factory()->create();
        $post->delete();

        $this->get(route('posts.show', $post))->assertNotFound();
    }
}
