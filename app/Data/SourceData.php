<?php

namespace App\Data;

use App\Enums\SourceType;
use App\Models\Source;
use App\Support\VisitorRegion;
use Spatie\LaravelData\Data;

class SourceData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public SourceType $type,
        /** Null when the site may not be linked for this visitor (North Korean sites, in South Korea). */
        public ?string $websiteUrl,
        /** The source forbids AI tools on its content, which readers are told. */
        public bool $forbidsAi,
    ) {}

    public static function fromModel(Source $source): self
    {
        return new self(
            id: $source->id,
            name: $source->name,
            slug: $source->slug,
            type: $source->type,
            websiteUrl: VisitorRegion::current()->allows($source->website_url) ? $source->website_url : null,
            forbidsAi: $source->forbids_ai,
        );
    }
}
