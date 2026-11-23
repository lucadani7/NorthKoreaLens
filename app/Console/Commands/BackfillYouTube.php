<?php

namespace App\Console\Commands;

use App\Feeds\FeedImporter;
use App\Feeds\YouTubeSearch;
use App\Models\Source;
use Illuminate\Console\Command;

class BackfillYouTube extends Command
{
    protected $signature = 'youtube:backfill
                            {source : Slug of a source whose feed is a YouTube channel, e.g. korea-now}
                            {--pages=4 : Pages of 50 results to read; each costs 100 of the 10,000 daily quota units}';

    protected $description = "Import older videos from a YouTube channel's archive that match the source's keywords";

    /**
     * The API cannot tell Shorts apart, only the length. Shorts last at most 3 minutes, so
     * sources without Shorts ask for videos of 4 minutes or more (regular ones under 4 are lost).
     */
    private const DURATIONS_WITHOUT_SHORTS = ['medium', 'long'];

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

        $pages = max(1, (int) $this->option('pages'));
        $videos = [];

        foreach ($source->skip_shorts ? self::DURATIONS_WITHOUT_SHORTS : [null] as $duration) {
            array_push($videos, ...$search->channelVideos($channelId, $source->keywords ?? [], $pages, $duration));
        }
        $imported = 0;

        foreach ($videos as $video) {
            // YouTube's search is loose, so our own keyword check runs again.
            $imported += $importer->import($source, $video) ? 1 : 0;
        }

        $this->info(sprintf(
            '%s: %d videos found, %d new %s.',
            $source->name,
            count($videos),
            $imported,
            $source->needs_review ? 'drafts to review in /admin' : 'posts',
        ));

        return self::SUCCESS;
    }
}
