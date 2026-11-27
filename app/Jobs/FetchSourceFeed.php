<?php

namespace App\Jobs;

use App\Feeds\FeedImporter;
use App\Feeds\FeedItem;
use App\Feeds\FeedParser;
use App\Feeds\GhostArchiveParser;
use App\Feeds\RodongParser;
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

    public function handle(FeedParser $parser, GhostArchiveParser $ghost, RodongParser $rodong, FeedImporter $importer): void
    {
        if (! $this->source->is_active || $this->source->feed_url === null) {
            return;
        }

        $isRodong = $this->source->feed_format === 'rodong';

        // The Rodong Sinmun site is slow (its home page is several megabytes) and often down.
        $response = Http::timeout($isRodong ? 300 : 20)
            ->retry($isRodong ? 3 : 1, $isRodong ? 60_000 : 0, throw: false)
            ->withUserAgent(config('app.name').' feed reader (+'.config('app.url').')')
            ->accept($isRodong ? 'text/html' : 'application/rss+xml, application/atom+xml, application/xml, text/xml')
            ->get($this->source->feed_url)
            ->throw();

        $items = match (true) {
            $isRodong => $rodong->parse($response->body(), $this->source->feed_url),
            $this->source->feed_format === 'ghost' => [
                ...$parser->parse($response->body()),
                ...$this->fetchGhostArchive($ghost),
            ],
            default => $parser->parse($response->body()),
        };

        foreach ($items as $item) {
            $importer->import($this->source, $item);
        }

        $this->source->forceFill(['last_fetched_at' => now()])->save();
    }

    /** @return list<FeedItem> */
    private function fetchGhostArchive(GhostArchiveParser $parser): array
    {
        $archiveUrl = $parser->archiveUrl((string) $this->source->feed_url);
        $response = Http::timeout(20)
            ->retry(1, 0, throw: false)
            ->withUserAgent(config('app.name').' feed reader (+'.config('app.url').')')
            ->accept('text/html')
            ->get($archiveUrl)
            ->throw();
        $html = $response->body();
        $items = $parser->parse($html, $archiveUrl);

        for ($page = 2, $pageCount = $parser->pageCount($html); $page <= $pageCount; $page++) {
            $pageUrl = rtrim($archiveUrl, '/')."/page/{$page}/";
            $pageResponse = Http::timeout(20)
                ->retry(1, 0, throw: false)
                ->withUserAgent(config('app.name').' feed reader (+'.config('app.url').')')
                ->accept('text/html')
                ->get($pageUrl)
                ->throw();

            array_push($items, ...$parser->parse($pageResponse->body(), $pageUrl));
        }

        return $items;
    }
}
