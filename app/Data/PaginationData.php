<?php

namespace App\Data;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Data;

class PaginationData extends Data
{
    public function __construct(
        public int $currentPage,
        public int $lastPage,
        public int $total,
        public ?string $previousPageUrl,
        public ?string $nextPageUrl,
    ) {}

    public static function fromPaginator(LengthAwarePaginator $paginator): self
    {
        return new self(
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
            total: $paginator->total(),
            previousPageUrl: $paginator->previousPageUrl(),
            nextPageUrl: $paginator->nextPageUrl(),
        );
    }
}
