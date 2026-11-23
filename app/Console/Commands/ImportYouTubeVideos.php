<?php

namespace App\Console\Commands;

use App\Feeds\FeedImporter;
use App\Feeds\YouTubeSearch;
use App\Models\Source;
use App\Support\YouTube;
use Illuminate\Console\Command;

class ImportYouTubeVideos extends Command
{
    protected $signature = 'youtube:import
                            {source : Slug of a source whose feed is a YouTube channel, e.g. charles-north-korea}
                            {links* : YouTube links of the videos to import}';

    protected $description = "Import chosen videos from a source's YouTube channel, by link";

    public function handle(YouTubeSearch $search, FeedImporter $importer): int
    {
        $source = Source::where('slug', $this->argument('source'))->first();
        $channelId = $source?->youtubeChannelId();

        if ($source === null || $channelId === null) {
            $this->error('No source with that slug and a YouTube channel feed.');

            return self::FAILURE;
        }

        $ids = [];

        foreach ((array) $this->argument('links') as $link) {
            $id = YouTube::videoId((string) $link);

            if ($id === null) {
                $this->warn("Not a YouTube video link: {$link}");

                continue;
            }

            $ids[] = $id;
        }

        if ($ids === []) {
            return self::FAILURE;
        }

        $found = [];

        foreach ($search->videos($ids) as ['item' => $item, 'channelId' => $videoChannel]) {
            $found[] = substr($item->guid, strlen('yt:video:'));

            // Only the source's own videos, so the yellow notice never lands on someone else's.
            if ($videoChannel !== $channelId) {
                $this->warn("Not on {$source->name}'s channel: {$item->title}");

                continue;
            }

            $this->line(match (true) {
                $importer->import($source, $item) => "Imported: {$item->title}",
                YouTube::isLivestreamTitle($item->title) => "Skipped, a live broadcast: {$item->title}",
                $source->wants($item->title.' '.$item->description) => "Already imported or deleted: {$item->title}",
                default => "Skipped, does not match the source's keywords: {$item->title}",
            });
        }

        foreach (array_diff($ids, $found) as $missing) {
            $this->warn("Not found on YouTube (private or removed?): {$missing}");
        }

        $this->info($source->needs_review ? 'New videos wait as drafts in /admin.' : 'Done.');

        return self::SUCCESS;
    }
}
