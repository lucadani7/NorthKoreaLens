<?php

namespace App\Data;

use App\Models\Post;
use Spatie\LaravelData\Data;

/**
 * A post as the feed shows it. Load the "source" and "category" relations first.
 */
class PostData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $excerpt,
        public ?string $originalUrl,
        public ?string $imageUrl,
        /** ISO 8601 date, e.g. 2026-11-22T14:30:00+00:00 */
        public ?string $publishedAt,
        public bool $isPropaganda,
        /** The team's context ("What the article doesn't say"). */
        public ?string $editorialNote,
        /** True for team posts with full text, shown on the post page. */
        public bool $hasBody,
        public CategoryData $category,
        public ?SourceData $source,
    ) {}

    public static function fromModel(Post $post): self
    {
        return new self(
            id: $post->id,
            title: $post->title,
            excerpt: $post->excerpt,
            originalUrl: $post->original_url,
            imageUrl: $post->image_url,
            publishedAt: $post->published_at?->toIso8601String(),
            isPropaganda: $post->isPropaganda(),
            editorialNote: $post->editorial_note,
            hasBody: filled($post->body),
            category: CategoryData::fromModel($post->category),
            source: $post->source ? SourceData::fromModel($post->source) : null,
        );
    }
}
