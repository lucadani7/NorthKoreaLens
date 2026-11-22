<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Source;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SourceSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_posts_from_state_media_are_propaganda_whatever_their_category(): void
    {
        $post = Post::factory()
            ->for(Source::factory()->stateMedia())
            ->create();

        $this->assertTrue($post->isPropaganda());
        $this->assertContains($post->id, Post::propaganda()->pluck('id'));
    }

    public function test_posts_in_the_propaganda_category_are_propaganda(): void
    {
        $post = Post::factory()
            ->for(Category::factory()->propaganda())
            ->create();

        $this->assertTrue($post->isPropaganda());
    }

    public function test_independent_news_is_not_propaganda(): void
    {
        $post = Post::factory()->create();

        $this->assertFalse($post->isPropaganda());
        $this->assertSame(0, Post::propaganda()->count());
    }

    public function test_the_feed_shows_only_published_posts_newest_first(): void
    {
        $older = Post::factory()->create(['published_at' => now()->subDays(2)]);
        $newer = Post::factory()->create(['published_at' => now()->subHour()]);
        Post::factory()->unpublished()->create();
        Post::factory()->create(['published_at' => now()->addDay()]);

        $this->assertSame([$newer->id, $older->id], Post::feed()->pluck('id')->all());
    }

    public function test_the_same_feed_item_cannot_be_imported_twice(): void
    {
        $source = Source::factory()->create();
        Post::factory()->for($source)->create(['guid' => 'item-1']);

        $this->expectException(UniqueConstraintViolationException::class);

        Post::factory()->for($source)->create(['guid' => 'item-1']);
    }

    public function test_the_mvp_categories_and_sources_are_seeded(): void
    {
        $this->seed([CategorySeeder::class, SourceSeeder::class]);

        $this->assertSame(6, Category::count());
        $this->assertTrue(Source::where('slug', 'rodong-sinmun')->first()->type->isPropaganda());
        // 38 North is seeded inactive, so only NK News and Daily NK are fetched.
        $this->assertSame(2, Source::fetchable()->count());
    }

    public function test_category_names_are_translated(): void
    {
        $category = Category::factory()->create(['slug' => 'news']);

        app()->setLocale('ro');

        $this->assertSame('Știri', $category->name());
    }
}
