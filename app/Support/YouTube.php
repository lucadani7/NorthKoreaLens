<?php

namespace App\Support;

final class YouTube
{
    /** The video id of a YouTube watch, short, Shorts or live link; null for any other address. */
    public static function videoId(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);

        $id = match (true) {
            $host === 'youtu.be' => ltrim($path, '/'),
            in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true) && $path === '/watch' => self::query($url, 'v'),
            in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true) && str_starts_with($path, '/shorts/') => substr($path, 8),
            in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true) && str_starts_with($path, '/live/') => substr($path, 6),
            default => null,
        };

        // Video ids are 11 characters of letters, digits, "-" and "_".
        return $id !== null && preg_match('/\A[A-Za-z0-9_-]{11}\z/', $id) === 1 ? $id : null;
    }

    /** Whether the link is a YouTube Shorts link (channel feeds link Shorts as /shorts/<id>). */
    public static function isShort(?string $url): bool
    {
        $host = strtolower((string) parse_url((string) $url, PHP_URL_HOST));

        return in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)
            && str_starts_with((string) parse_url((string) $url, PHP_URL_PATH), '/shorts/');
    }

    /**
     * Whether a video title marks a live broadcast: "live show", "livestream" or "live stream"
     * in any case, or "LIVE" as a word in capitals. A lower-case "live" alone ("live-fire
     * drills", "where they live") does not count.
     */
    public static function isLivestreamTitle(string $title): bool
    {
        return preg_match('/\\blive\\s*-?\\s*(?:show|stream)/iu', $title) === 1
            || preg_match('/(?<![\\p{L}\\p{N}])LIVE(?![\\p{L}\\p{N}])/u', $title) === 1;
    }

    private static function query(string $url, string $key): ?string
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return is_string($query[$key] ?? null) ? $query[$key] : null;
    }
}
