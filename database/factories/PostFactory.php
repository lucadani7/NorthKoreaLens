<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Post;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'source_id' => Source::factory(),
            'category_id' => Category::factory(),
            'title' => fake()->sentence(),
            'excerpt' => fake()->paragraph(),
            'original_url' => fake()->url(),
            'guid' => fake()->uuid(),
            'language' => 'en',
            'published_at' => fake()->dateTimeBetween('-1 week'),
        ];
    }

    /** A post written by our own team, with full text and no external source. */
    public function ownContent(): static
    {
        return $this->state(fn () => [
            'source_id' => null,
            'guid' => null,
            'original_url' => null,
            'body' => fake()->paragraphs(4, true),
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['published_at' => null]);
    }
}
