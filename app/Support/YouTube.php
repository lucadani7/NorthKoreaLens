<?php

namespace App\Support;

final class YouTube
{
    /** The video id of a YouTube watch, short or Shorts link; null for any other address. */
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
            default => null,
        };

        // Video ids are 11 characters of letters, digits, "-" and "_".
        return $id !== null && preg_match('/\A[A-Za-z0-9_-]{11}\z/', $id) === 1 ? $id : null;
    }

    private static function query(string $url, string $key): ?string
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return is_string($query[$key] ?? null) ? $query[$key] : null;
    }
}
