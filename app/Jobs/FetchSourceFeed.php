<?php

namespace App\Jobs;

use App\Feeds\FeedParser;
use App\Models\Category;
use App\Models\Post;
use App\Models\Source;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Reads one source's RSS feed and stores the articles we have not seen yet.
 */
class FetchSourceFeed implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** Seconds to wait before each retry. */
    public array $backoff = [60, 300];

    public function __construct(public Source $source) {}

    public function handle(FeedParser $parser): void
    {
        if (! $this->source->is_active || $this->source->feed_url === null) {
            return;
        }

        $response = Http::timeout(20)
            ->withUserAgent(config('app.name').' feed reader (+'.config('app.url').')')
            ->accept('application/rss+xml, application/atom+xml, application/xml, text/xml')
            ->get($this->source->feed_url)
            ->throw();

        $category = Category::where('slug', $this->source->type->defaultCategorySlug())->firstOrFail();

        foreach ($parser->parse($response->body()) as $item) {
            Post::firstOrCreate(
                ['source_id' => $this->source->id, 'guid' => Str::limit($item->guid, 250, '')],
                [
                    'category_id' => $category->id,
                    'title' => Str::limit($item->title, 250),
                    'excerpt' => $item->excerpt,
                    'original_url' => $item->link,
                    'image_url' => $item->imageUrl,
                    'language' => $this->source->language,
                    // A feed item without a date is shown as published when we first saw it.
                    'published_at' => $item->publishedAt ? Carbon::instance($item->publishedAt) : now(),
                ],
            );
        }

        $this->source->forceFill(['last_fetched_at' => now()])->save();
    }
}
