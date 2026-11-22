<?php

namespace App\Feeds;

use DateTimeImmutable;
use Exception;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Searches a YouTube channel's whole archive through the YouTube Data API v3,
 * for the videos an RSS feed no longer lists (it only has the latest 15).
 * Each page costs 100 of the 10,000 free daily quota units.
 */
class YouTubeSearch
{
    private const ENDPOINT = 'https://www.googleapis.com/youtube/v3/search';

    /**
     * @param  list<string>  $keywords  Matched with YouTube's OR operator.
     * @return list<FeedItem>
     */
    public function channelVideos(string $channelId, array $keywords, int $maxPages): array
    {
        $key = config('services.youtube.key');

        if (! is_string($key) || $key === '') {
            throw new RuntimeException('Set YOUTUBE_API_KEY in .env to search YouTube.');
        }

        $query = implode('|', array_map(
            fn (string $keyword) => str_contains($keyword, ' ') ? '"'.$keyword.'"' : $keyword,
            array_values(array_filter(array_map('trim', $keywords))),
        ));

        $items = [];
        $pageToken = null;

        for ($page = 0; $page < $maxPages; $page++) {
            $response = Http::timeout(20)->get(self::ENDPOINT, array_filter([
                'part' => 'snippet',
                'channelId' => $channelId,
                'q' => $query,
                'type' => 'video',
                'order' => 'date',
                'maxResults' => 50,
                'pageToken' => $pageToken,
                'key' => $key,
            ]))->throw();

            foreach ($response->json('items', []) as $result) {
                if ($item = $this->toFeedItem($result)) {
                    $items[] = $item;
                }
            }

            $pageToken = $response->json('nextPageToken');

            if (! is_string($pageToken)) {
                break;
            }
        }

        return $items;
    }

    /** @param  array<string, mixed>  $result */
    private function toFeedItem(array $result): ?FeedItem
    {
        $id = data_get($result, 'id.videoId');
        $title = $this->text(data_get($result, 'snippet.title'));

        if (! is_string($id) || preg_match('/\A[A-Za-z0-9_-]{11}\z/', $id) !== 1 || $title === '') {
            return null;
        }

        $description = $this->text(data_get($result, 'snippet.description'));
        $thumbnail = data_get($result, 'snippet.thumbnails.high.url') ?? data_get($result, 'snippet.thumbnails.default.url');

        return new FeedItem(
            // Same guid as the channel's RSS feed, so a video is never imported twice.
            guid: "yt:video:{$id}",
            title: $title,
            link: "https://www.youtube.com/watch?v={$id}",
            excerpt: $description !== '' ? $description : null,
            imageUrl: is_string($thumbnail) && str_starts_with($thumbnail, 'https://') ? $thumbnail : null,
            publishedAt: $this->date(data_get($result, 'snippet.publishedAt')),
            description: $description,
        );
    }

    /** The API sends HTML entities ("&#39;") in titles and descriptions. */
    private function text(mixed $value): string
    {
        return is_string($value) ? trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')) : '';
    }

    private function date(mixed $value): ?DateTimeImmutable
    {
        try {
            return is_string($value) ? new DateTimeImmutable($value) : null;
        } catch (Exception) {
            return null;
        }
    }
}
