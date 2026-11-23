<?php

namespace Database\Factories;

use App\Enums\SourceType;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Source>
 */
class SourceFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => SourceType::Independent,
            'website_url' => fake()->url(),
            'feed_url' => fake()->url().'/feed',
            'feed_format' => 'feed',
            'fetch_every_hours' => 1,
            'language' => 'en',
            'is_active' => true,
        ];
    }

    public function stateMedia(): static
    {
        return $this->state(fn () => ['type' => SourceType::StateMedia]);
    }

    public function withoutFeed(): static
    {
        return $this->state(fn () => ['feed_url' => null]);
    }
}
