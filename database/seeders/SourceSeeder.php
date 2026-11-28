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
        // 38 North's Cloudflare challenge returns HTTP 403 even with browser-like feed headers,
        // so it stays inactive until a different feed URL or an approved server-side access path exists.
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
                // No RSS feed: the headlines are read from the English home page once a day
                // and wait as drafts, so the team can add "What the article doesn't say".
                'name' => 'Rodong Sinmun',
                'slug' => 'rodong-sinmun',
                'type' => SourceType::StateMedia,
                'website_url' => 'http://www.rodong.rep.kp',
                'feed_url' => 'http://www.rodong.rep.kp/en/',
                'feed_format' => 'rodong',
                'fetch_every_hours' => 24,
                'needs_review' => true,
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

        // The South China Morning Post's North Korea topic feed (checked on 2026-11-23). It also
        // tags China and Russia stories that only mention the North in passing, so only articles
        // that name North Korea or its leaders are kept. The list is stricter than the KOREA NOW
        // one: words like "unification" or "defector" also appear in China stories.
        $sources[] = [
            'name' => 'South China Morning Post',
            'slug' => 'scmp',
            'type' => SourceType::Independent,
            'website_url' => 'https://www.scmp.com/topics/north-korea',
            'feed_url' => 'https://www.scmp.com/rss/33318/feed',
            'keywords' => [
                'North Korea', 'North Korean', 'N. Korea', 'N.Korea', 'DPRK', 'Pyongyang',
                'Kim Jong Un', 'Kim Jong-un', 'Kim Yo Jong', 'Kim Yo-jong', 'Kim Ju Ae', 'Kim Ju-ae',
                'inter-Korean', 'Panmunjom',
            ],
        ];

        // National Geographic covers many subjects; matching videos wait for review in Documentaries.
        $sources[] = [
            'name' => 'National Geographic',
            'slug' => 'national-geographic',
            'type' => SourceType::Independent,
            'category_id' => Category::where('slug', 'documentaries')->value('id'),
            'website_url' => 'https://www.nationalgeographic.com',
            'feed_url' => 'https://www.youtube.com/feeds/videos.xml?channel_id=UCpVm7bg6pXKo1Pr6k5kxG9A',
            'keywords' => [
                'North Korea', 'North Korean', 'DPRK', 'Pyongyang',
                'Kim Jong Un', 'Kim Jong-un', 'Kim Il Sung', 'Kim Il-sung',
                'Kim Jong Il', 'Kim Jong-il', 'Korean Peninsula', 'inter-Korean',
                'DMZ', 'Korean Demilitarized Zone', 'Panmunjom', 'Korean War',
                'Juche', 'Paektu', 'Mount Paektu', 'Kaesong', 'Rason',
                'Sinuiju', 'Wonsan', 'Hamhung', 'Chongjin',
                'North Korean defector', 'North Korean defectors',
            ],
            'language' => 'en',
            'needs_review' => true,
            'forbids_ai' => false,
            'skip_shorts' => true,
        ];

        // UHN Plus provides a dedicated Spanish-language North Korea RSS feed.
        $sources[] = [
            'name' => 'UHN Plus',
            'slug' => 'uhn-plus',
            'type' => SourceType::Independent,
            'website_url' => 'https://www.uhnplus.com',
            'feed_url' => 'https://www.uhnplus.com/tag/corea-del-norte/rss/',
            'feed_format' => 'ghost',
            'language' => 'es',
            'forbids_ai' => false,
        ];

        // Charles Ryu, a North Korean escapee in the US, on YouTube. His videos wait as drafts so
        // the team picks the testimonies worth showing (not the reactions or street interviews).
        // Readers see the notice in lang/<locale>/source_notices.php on every post.
        $sources[] = [
            'name' => 'Charles North Korea (Charles Ryu)',
            'slug' => 'charles-north-korea',
            'type' => SourceType::Testimony,
            'website_url' => 'https://www.youtube.com/@charlesnorthkorea',
            'feed_url' => 'https://www.youtube.com/feeds/videos.xml?channel_id=UCv_zJYPJndI4JH4P_p-eY5w',
            'needs_review' => true,
            'skip_shorts' => true,
        ];

        // HaiHui în doi, the Romanian travel channel that inspired NK Lens. It covers many
        // countries, so only videos that mention North Korea are imported, as drafts the team
        // picks from. The channel id was read from the channel page on 2026-11-24.
        $sources[] = [
            'name' => 'HaiHui în doi',
            'slug' => 'haihui-in-doi',
            'type' => SourceType::TravelVlog,
            'website_url' => 'https://www.youtube.com/@haihuin2',
            'feed_url' => 'https://www.youtube.com/feeds/videos.xml?channel_id=UCAE8fRq0xXh6gZ6d2EHYipQ',
            'keywords' => [
                'Coreea de Nord', 'Coreei de Nord', 'nord-core', 'Phenian', 'Rason',
                'North Korea', 'Pyongyang', 'Kim Jong Un', 'Kim Jong-un',
            ],
            'needs_review' => true,
            'skip_shorts' => true,
        ];

        foreach ($sources as $source) {
            Source::updateOrCreate(['slug' => $source['slug']], $source);
        }
    }
}
