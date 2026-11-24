<?php

namespace App\Data;

use App\Models\Post;
use App\Models\Topic;
use App\Support\VisitorRegion;
use App\Support\YouTube;
use Spatie\LaravelData\Data;

/**
 * A post as the feed shows it. Load the "source" and "category" relations first.
 * Links and images from North Korean sites are left out for visitors in South Korea.
 */
class PostData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $excerpt,
        /** Null when there is no original, or when it may not be linked for this visitor. */
        public ?string $originalUrl,
        public ?string $imageUrl,
        /** Author and licence of the image, e.g. "Photo: …, CC BY-SA 4.0, via Wikimedia Commons". */
        public ?string $imageCredit,
        /** ISO 8601 date, e.g. 2026-11-22T14:30:00+00:00 */
        public ?string $publishedAt,
        public bool $isPropaganda,
        /** The team's context ("What the article doesn't say"). */
        public ?string $editorialNote,
        /** A short verse or quote shown centred above the text, the same in every language. */
        public ?string $epigraph,
        /** True for team posts with full text, shown on the post page. */
        public bool $hasBody,
        /** True when the original exists but is not linked for this visitor (South Korea). */
        public bool $linkWithheld,
        /** Set when the original is a YouTube video, so it can be played on the page. */
        public ?string $youtubeId,
        public CategoryData $category,
        /** @var list<TopicData> */
        public array $topics,
        /** The language of title, excerpt and note: the reader's, or the post's own when not translated. */
        public string $contentLocale,
        public ?SourceData $source,
    ) {}

    public static function fromModel(Post $post): self
    {
        $region = VisitorRegion::current();
        $linkAllowed = $region->allows($post->original_url);
        $locale = app()->getLocale();

        return new self(
            id: $post->id,
            title: (string) $post->localized('title', $locale),
            excerpt: $post->localized('excerpt', $locale),
            originalUrl: $linkAllowed ? $post->original_url : null,
            imageUrl: $region->allows($post->image_url) ? $post->image_url : null,
            imageCredit: $region->allows($post->image_url) ? $post->image_credit : null,
            publishedAt: $post->published_at?->toIso8601String(),
            isPropaganda: $post->isPropaganda(),
            editorialNote: $post->localized('editorial_note', $locale),
            epigraph: $post->epigraph,
            hasBody: filled($post->body),
            linkWithheld: ! $linkAllowed,
            youtubeId: $linkAllowed ? YouTube::videoId($post->original_url) : null,
            category: CategoryData::fromModel($post->category),
            topics: array_values($post->topics->sortBy('sort_order')->map(fn (Topic $topic) => TopicData::fromModel($topic))->all()),
            contentLocale: $post->contentLocale($locale),
            source: $post->source ? SourceData::fromModel($post->source) : null,
        );
    }
}
