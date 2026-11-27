<?php

namespace App\Console\Commands;

use App\Feeds\FeedImporter;
use App\Feeds\YouTubeSearch;
use App\Models\Source;
use Illuminate\Console\Command;

class BackfillYouTube extends Command
{
    protected $signature = 'youtube:backfill
                            {source : Slug of a source whose feed is a YouTube channel, e.g. korea-now or national-geographic}';

    protected $description = "Scan a YouTube channel's complete uploads playlist and import keyword matches";

    private const SHORTS_MAX_SECONDS = 180;

    public function handle(YouTubeSearch $search, FeedImporter $importer): int
    {
        $source = Source::where('slug', $this->argument('source'))->first();
        $channelId = $source?->youtubeChannelId();

        if ($source === null || $channelId === null) {
            $this->error('No source with that slug and a YouTube channel feed.');

            return self::FAILURE;
        }

        // Without keywords the whole channel comes in, which is only safe as drafts to review.
        if (blank($source->keywords) && ! $source->needs_review) {
            $this->error("{$source->name} has no keywords and publishes directly, so the whole channel would go online.");

            return self::FAILURE;
        }

        $ids = $search->channelUploadIds($channelId);
        $videos = $search->videos($ids);
        $matches = 0;
        $imported = 0;

        foreach ($videos as $video) {
            if ($video['channelId'] !== $channelId
                || ($source->skip_shorts && ($video['seconds'] === null || $video['seconds'] <= self::SHORTS_MAX_SECONDS))) {
                continue;
            }

            $item = $video['item'];

            if (! $source->wants($item->title.' '.$item->description)) {
                continue;
            }

            $matches++;
            $imported += $importer->import($source, $item) ? 1 : 0;
        }

        $this->info(sprintf(
            '%s: %d uploads listed, %d videos available, %d keyword matches, %d new %s.',
            $source->name,
            count($ids),
            count($videos),
            $matches,
            $imported,
            $source->needs_review ? 'drafts' : 'posts',
        ));

        return self::SUCCESS;
    }
}
