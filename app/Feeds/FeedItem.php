<?php

namespace App\Feeds;

use DateTimeImmutable;

/**
 * One article read from an RSS or Atom feed, before it becomes a Post.
 */
final readonly class FeedItem
{
    public function __construct(
        public string $guid,
        public string $title,
        public string $link,
        public ?string $excerpt,
        public ?string $imageUrl,
        public ?DateTimeImmutable $publishedAt,
        /** The whole description as plain text, used to match the source's keywords. */
        public string $description = '',
        /** @var list<string> */
        public array $categories = [],
    ) {}
}
