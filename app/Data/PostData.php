<?php

namespace App\Data;

use App\Models\Post;
use App\Support\VisitorRegion;
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
        /** ISO 8601 date, e.g. 2026-11-22T14:30:00+00:00 */
        public ?string $publishedAt,
        public bool $isPropaganda,
        /** The team's context ("What the article doesn't say"). */
        public ?string $editorialNote,
        /** True for team posts with full text, shown on the post page. */
        public bool $hasBody,
        /** True when the original exists but is not linked for this visitor (South Korea). */
        public bool $linkWithheld,
        public CategoryData $category,
        public ?SourceData $source,
    ) {}

    public static function fromModel(Post $post): self
    {
        $region = VisitorRegion::current();
        $linkAllowed = $region->allows($post->original_url);

        return new self(
            id: $post->id,
            title: $post->title,
            excerpt: $post->excerpt,
            originalUrl: $linkAllowed ? $post->original_url : null,
            imageUrl: $region->allows($post->image_url) ? $post->image_url : null,
            publishedAt: $post->published_at?->toIso8601String(),
            isPropaganda: $post->isPropaganda(),
            editorialNote: $post->editorial_note,
            hasBody: filled($post->body),
            linkWithheld: ! $linkAllowed,
            category: CategoryData::fromModel($post->category),
            source: $post->source ? SourceData::fromModel($post->source) : null,
        );
    }
}
