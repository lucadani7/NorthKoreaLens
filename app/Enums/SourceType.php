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

    /** A North Korean escapee telling their own story, e.g. a YouTube channel. */
    case Testimony = 'testimony';

    /** A travel channel that has visited North Korea on an organised tour, e.g. on YouTube. */
    case TravelVlog = 'travel_vlog';

    /** North Korean state media (e.g. Rodong Sinmun, KCNA). Always shown as propaganda. */
    case StateMedia = 'state_media';

    public function isPropaganda(): bool
    {
        return $this === self::StateMedia;
    }

    /** The category that imported posts from this kind of source land in. */
    public function defaultCategorySlug(): string
    {
        return match ($this) {
            self::Independent => 'news',
            self::Analysis => 'analysis',
            self::Testimony => 'human-rights',
            self::TravelVlog => 'travel-vlogs',
            self::StateMedia => 'propaganda',
        };
    }
}
