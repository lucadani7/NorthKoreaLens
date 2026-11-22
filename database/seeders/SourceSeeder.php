<?php

namespace Database\Seeders;

use App\Enums\SourceType;
use App\Models\Category;
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

        // Yonhap's English YouTube channel covers all of Korea, mostly the South. Only videos
        // that mention North Korea are imported, and they wait as drafts for review.
        $sources[] = [
            'name' => 'KOREA NOW (Yonhap)',
            'slug' => 'korea-now',
            'type' => SourceType::Independent,
            'category_id' => Category::where('slug', Category::KOREA_NOW)->value('id'),
            'website_url' => 'https://www.youtube.com/@KOREANOWyna',
            'feed_url' => 'https://www.youtube.com/feeds/videos.xml?channel_id=UCR-L8oZkHzgDh1ahJhH2WDQ',
            'keywords' => [
                'North Korea', 'N. Korea', 'N.Korea', 'DPRK', 'Pyongyang',
                'Kim Jong Un', 'Kim Jong-un', 'Kim Yo Jong', 'Kim Yo-jong', 'Kim Ju Ae', 'Kim Ju-ae',
                'inter-Korean', 'defector', 'Panmunjom', 'DMZ', 'Korean War', 'unification',
                '북한', '평양', '김정은',
            ],
            'needs_review' => true,
            // Every KOREA NOW description says AI tools may not be used on Yonhap's videos.
            'forbids_ai' => true,
        ];

        foreach ($sources as $source) {
            Source::updateOrCreate(['slug' => $source['slug']], $source);
        }
    }
}
