<?php

namespace Database\Seeders;

use App\Enums\SourceType;
use App\Models\Source;
use Illuminate\Database\Seeder;

class SourceSeeder extends Seeder
{
    public function run(): void
    {
        // NK News and Daily NK feeds checked on 2026-11-22 (RSS 2.0).
        // 38 North sits behind a Cloudflare challenge that blocks feed readers (HTTP 403),
        // so it stays inactive until they allow our reader or we add its posts by hand.
        $sources = [
            [
                'name' => 'NK News',
                'slug' => 'nk-news',
                'type' => SourceType::Independent,
                'website_url' => 'https://www.nknews.org',
                'feed_url' => 'https://www.nknews.org/feed/',
            ],
            [
                'name' => 'Daily NK',
                'slug' => 'daily-nk',
                'type' => SourceType::Independent,
                'website_url' => 'https://www.dailynk.com/english/',
                'feed_url' => 'https://www.dailynk.com/english/feed/',
            ],
            [
                'name' => '38 North',
                'slug' => '38-north',
                'type' => SourceType::Analysis,
                'website_url' => 'https://www.38north.org',
                'feed_url' => 'https://www.38north.org/feed/',
                'is_active' => false,
            ],
            [
                // No public RSS feed: posts are added through KCNA Watch or by hand.
                'name' => 'Rodong Sinmun',
                'slug' => 'rodong-sinmun',
                'type' => SourceType::StateMedia,
                'website_url' => 'http://www.rodong.rep.kp',
                'feed_url' => null,
            ],
        ];

        foreach ($sources as $source) {
            Source::updateOrCreate(['slug' => $source['slug']], $source);
        }
    }
}
