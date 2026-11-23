<?php

namespace App\Console\Commands;

use App\Feeds\FeedImporter;
use App\Feeds\FeedItem;
use App\Models\Source;
use Illuminate\Console\Command;

class CleanExcerpts extends Command
{
    protected $signature = 'posts:clean-excerpts
                            {source : Slug of a source, e.g. charles-north-korea}
                            {--dry-run : Only list what would change}';

    protected $description = 'Clean the excerpts of all posts from a source again (drafts and published), e.g. after new promotion rules';

    public function handle(): int
    {
        $source = Source::where('slug', $this->argument('source'))->first();

        if ($source === null) {
            $this->error('No source with that slug.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $changed = 0;

        foreach ($source->posts()->whereNotNull('excerpt')->get() as $post) {
            $excerpt = FeedImporter::excerpt(new FeedItem(
                guid: (string) $post->guid,
                title: $post->title,
                link: (string) $post->original_url,
                excerpt: null,
                imageUrl: null,
                publishedAt: null,
                description: (string) $post->excerpt,
            ));

            if ($excerpt === $post->excerpt) {
                continue;
            }

            $changed++;
            $this->line("{$post->title}: ".($excerpt ?? '(no excerpt)'));

            if (! $dryRun) {
                $post->update(['excerpt' => $excerpt]);
            }
        }

        $this->info(sprintf('%s %d excerpts.', $dryRun ? 'Would clean' : 'Cleaned', $changed));

        return self::SUCCESS;
    }
}
