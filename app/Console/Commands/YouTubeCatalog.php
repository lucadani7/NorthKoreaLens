<?php

namespace App\Console\Commands;

use App\Feeds\YouTubeSearch;
use App\Models\Source;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class YouTubeCatalog extends Command
{
    protected $signature = 'youtube:catalog
                            {source : Slug of a source whose feed is a YouTube channel, e.g. charles-north-korea}
                            {--pages=10 : Pages of 50 search results per length; each costs 100 of the 10,000 daily quota units}';

    protected $description = "List a YouTube channel's videos (no Shorts) in a JSON file, to choose which to import";

    /** Shorts last at most 3 minutes. */
    private const SHORTS_MAX_SECONDS = 180;

    public function handle(YouTubeSearch $search): int
    {
        $source = Source::where('slug', $this->argument('source'))->first();
        $channelId = $source?->youtubeChannelId();

        if ($source === null || $channelId === null) {
            $this->error('No source with that slug and a YouTube channel feed.');

            return self::FAILURE;
        }

        $pages = max(1, (int) $this->option('pages'));
        $ids = [];

        // Search finds no Shorts among videos of 4 minutes or more.
        foreach (['medium', 'long'] as $duration) {
            foreach ($search->channelVideos($channelId, [], $pages, $duration) as $item) {
                $ids[] = substr($item->guid, strlen('yt:video:'));
            }
        }

        $imported = $source->posts()->withTrashed()->pluck('guid')->all();
        $videos = [];

        foreach ($search->videos($ids) as $video) {
            if ($video['channelId'] !== $channelId || ($video['seconds'] ?? 0) <= self::SHORTS_MAX_SECONDS) {
                continue;
            }

            $item = $video['item'];
            $videos[] = [
                'link' => $item->link,
                'title' => $item->title,
                'published' => $item->publishedAt?->format('Y-m-d'),
                'minutes' => intdiv((int) $video['seconds'], 60),
                'views' => $video['views'],
                'already_imported' => in_array($item->guid, $imported, true),
                'description' => $item->description,
            ];
        }

        usort($videos, fn (array $a, array $b) => strcmp((string) $b['published'], (string) $a['published']));

        $path = "youtube/{$source->slug}.json";
        Storage::disk('local')->put($path, (string) json_encode($videos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->info(sprintf('%d videos written to %s', count($videos), Storage::disk('local')->path($path)));

        return self::SUCCESS;
    }
}
