<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function propaganda(): static
    {
        return $this->state(fn () => ['slug' => Category::PROPAGANDA]);
    }
}
