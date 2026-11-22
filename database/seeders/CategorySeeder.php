<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /** The sections of the MVP, in the order they appear in the menu. */
    private const SLUGS = [
        'news',
        'analysis',
        'human-rights',
        'curiosities',
        Category::PROPAGANDA,
        'discussions',
    ];

    public function run(): void
    {
        foreach (self::SLUGS as $index => $slug) {
            Category::updateOrCreate(['slug' => $slug], ['sort_order' => $index + 1]);
        }
    }
}
