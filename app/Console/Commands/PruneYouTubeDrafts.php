<?php

namespace App\Console\Commands;

use App\Feeds\FeedImporter;
use App\Feeds\FeedItem;
use App\Models\Source;
use App\Support\YouTube;
use Illuminate\Console\Command;

class PruneYouTubeDrafts extends Command
{
    protected $signature = 'youtube:prune
                            {source : Slug of a YouTube source, e.g. charles-north-korea}
                            {keep : File with the links of the drafts to keep, one per line ("#" starts a comment)}
                            {--dry-run : Only list what would happen}';

    protected $description = "Delete a YouTube source's drafts except the chosen ones, and clean the excerpts of those kept";

    public function handle(): int
    {
        $source = Source::where('slug', $this->argument('source'))->first();
        $file = (string) $this->argument('keep');

        if ($source === null || $source->youtubeChannelId() === null) {
            $this->error('No source with that slug and a YouTube channel feed.');

            return self::FAILURE;
        }

        if (! is_file($file)) {
            $this->error("No such file: {$file}");

            return self::FAILURE;
        }

        $keep = [];

        foreach ((array) file($file, FILE_IGNORE_NEW_LINES) as $line) {
            $link = trim(explode('#', (string) $line, 2)[0]);

            if ($link !== '' && ($id = YouTube::videoId($link)) !== null) {
                $keep[] = "yt:video:{$id}";
            }
        }

        // An empty or unreadable list would delete every draft.
        if ($keep === []) {
            $this->error('The file lists no YouTube links, so nothing was deleted.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $deleted = 0;
        $kept = 0;

        // Only drafts: published posts are never touched.
        foreach ($source->posts()->whereNull('published_at')->get() as $post) {
            if (! in_array($post->guid, $keep, true)) {
                $deleted++;
                $dryRun ? $this->line("Would delete: {$post->title}") : $post->delete();

                continue;
            }

            $kept++;
            $excerpt = FeedImporter::excerpt(new FeedItem(
                guid: $post->guid,
                title: $post->title,
                link: (string) $post->original_url,
                excerpt: null,
                imageUrl: null,
                publishedAt: null,
                description: (string) $post->excerpt,
            ));

            if (! $dryRun && $excerpt !== $post->excerpt) {
                $post->update(['excerpt' => $excerpt]);
            }
        }

        $this->info(sprintf(
            '%s %d drafts, kept %d. Deleted drafts are never imported again.',
            $dryRun ? 'Would delete' : 'Deleted',
            $deleted,
            $kept,
        ));

        return self::SUCCESS;
    }
}
