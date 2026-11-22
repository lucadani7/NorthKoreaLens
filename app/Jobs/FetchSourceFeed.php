<?php

namespace App\Jobs;

use App\Feeds\FeedImporter;
use App\Feeds\FeedParser;
use App\Models\Source;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

/**
 * Reads one source's RSS or Atom feed (YouTube channels included) and stores the items we
 * have not seen yet, keeping only those that match the source's keywords.
 */
class FetchSourceFeed implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Seconds to wait before each retry.
     *
     * @var list<int>
     */
    public array $backoff = [60, 300];

    public function __construct(public Source $source) {}

    public function handle(FeedParser $parser, FeedImporter $importer): void
    {
        if (! $this->source->is_active || $this->source->feed_url === null) {
            return;
        }

        $response = Http::timeout(20)
            ->withUserAgent(config('app.name').' feed reader (+'.config('app.url').')')
            ->accept('application/rss+xml, application/atom+xml, application/xml, text/xml')
            ->get($this->source->feed_url)
            ->throw();

        foreach ($parser->parse($response->body()) as $item) {
            $importer->import($this->source, $item);
        }

        $this->source->forceFill(['last_fetched_at' => now()])->save();
    }
}
