<?php

namespace Database\Seeders;

use App\Models\Topic;
use Illuminate\Database\Seeder;

class TopicSeeder extends Seeder
{
    /** Themes for team posts, in the order the filters show them. */
    private const SLUGS = [
        'history',
        'leaders',
        'language',
        'places',
        'daily-life',
        'romania',
        'gdr',
    ];

    public function run(): void
    {
        foreach (self::SLUGS as $index => $slug) {
            Topic::updateOrCreate(['slug' => $slug], ['sort_order' => $index + 1]);
        }
    }
}
