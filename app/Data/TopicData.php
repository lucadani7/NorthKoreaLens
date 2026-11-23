<?php

namespace App\Data;

use App\Models\Topic;
use Spatie\LaravelData\Data;

class TopicData extends Data
{
    public function __construct(
        public int $id,
        public string $slug,
        /** Translated into the visitor's language. */
        public string $name,
    ) {}

    public static function fromModel(Topic $topic): self
    {
        return new self(id: $topic->id, slug: $topic->slug, name: $topic->name());
    }
}
