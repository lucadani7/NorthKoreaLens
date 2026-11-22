<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Rules that depend on the country the visitor is in.
 *
 * South Korea's National Security Act restricts access to North Korean websites,
 * so visitors there get no links to, and no images from, sites under .kp.
 */
final class VisitorRegion
{
    public function __construct(
        /** ISO 3166-1 alpha-2 code, e.g. "KR"; null when unknown. */
        public readonly ?string $country,
    ) {}

    public static function fromRequest(Request $request): self
    {
        // Cloudflare adds the visitor's country to every request. Without Cloudflare
        // (locally, in tests) the NK_LENS_VISITOR_COUNTRY setting stands in for it.
        $country = $request->header('CF-IPCountry') ?: config('nklens.visitor_country');

        return new self(is_string($country) && $country !== '' ? strtoupper($country) : null);
    }

    public static function current(): self
    {
        return self::fromRequest(request());
    }

    public function isSouthKorea(): bool
    {
        return $this->country === 'KR';
    }

    /** Whether we may link to this address, or load an image from it, for this visitor. */
    public function allows(?string $url): bool
    {
        if ($url === null || ! $this->isSouthKorea()) {
            return true;
        }

        $host = strtolower(rtrim((string) parse_url($url, PHP_URL_HOST), '.'));

        return $host !== 'kp' && ! str_ends_with($host, '.kp');
    }
}
