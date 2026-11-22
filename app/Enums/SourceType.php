<?php

namespace App\Enums;

/**
 * How trustworthy a news source is, which decides how its posts are labelled.
 */
enum SourceType: string
{
    /** Independent newsrooms with editorial standards (e.g. NK News, Daily NK). */
    case Independent = 'independent';

    /** Think tanks and research outlets (e.g. 38 North). */
    case Analysis = 'analysis';

    /** North Korean state media (e.g. Rodong Sinmun, KCNA). Always shown as propaganda. */
    case StateMedia = 'state_media';

    public function isPropaganda(): bool
    {
        return $this === self::StateMedia;
    }
}
