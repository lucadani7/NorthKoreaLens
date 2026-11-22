<?php

namespace App\Data;

use App\Enums\SourceType;
use App\Models\Source;
use Spatie\LaravelData\Data;

/** A source as listed on the Sources page. */
class SourceListingData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public SourceType $type,
        /** Null for state media: we do not link to North Korean state sites. */
        public ?string $websiteUrl,
    ) {}

    public static function fromModel(Source $source): self
    {
        return new self(
            id: $source->id,
            name: $source->name,
            type: $source->type,
            websiteUrl: $source->type->isPropaganda() ? null : $source->website_url,
        );
    }
}
