<?php

namespace App\Data;

use App\Enums\SourceType;
use App\Models\Source;
use Spatie\LaravelData\Data;

class SourceData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public SourceType $type,
        public string $websiteUrl,
    ) {}

    public static function fromModel(Source $source): self
    {
        return new self(
            id: $source->id,
            name: $source->name,
            slug: $source->slug,
            type: $source->type,
            websiteUrl: $source->website_url,
        );
    }
}
