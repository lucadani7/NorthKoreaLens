<?php

namespace App\Console\Commands;

use App\Jobs\FetchSourceFeed;
use App\Models\Source;
use Illuminate\Console\Command;
use Throwable;

class FetchFeeds extends Command
{
    protected $signature = 'feeds:fetch
                            {source? : Slug of a single source to fetch}
                            {--sync : Fetch right away instead of queueing the jobs}';

    protected $description = 'Fetch the latest articles from every active source with an RSS feed';

    public function handle(): int
    {
        $sources = Source::fetchable()
            ->when($this->argument('source'), fn ($query, $slug) => $query->where('slug', $slug))
            ->get();

        if ($sources->isEmpty()) {
            $this->warn('No fetchable sources found.');

            return self::FAILURE;
        }

        $failed = 0;

        foreach ($sources as $source) {
            if (! $this->option('sync')) {
                FetchSourceFeed::dispatch($source);
                $this->info("Queued {$source->name}.");

                continue;
            }

            // One broken feed must not stop the others.
            try {
                FetchSourceFeed::dispatchSync($source);
                $this->info("Fetched {$source->name}: {$source->posts()->count()} posts in total.");
            } catch (Throwable $e) {
                $failed++;
                report($e);
                $this->error("Could not fetch {$source->name}: ".class_basename($e).' '.$this->shortMessage($e));
            }
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function shortMessage(Throwable $e): string
    {
        return str($e->getMessage())->before("\n")->limit(120)->toString();
    }
}
