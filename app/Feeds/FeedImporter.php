<?php

namespace App\Feeds;

use App\Models\Post;
use App\Models\Source;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Stores feed items as posts of a source: only the ones that match its keywords,
 * in its section, and as drafts when the source needs review.
 */
class FeedImporter
{
    /** @return bool True when the item became a new post. */
    public function import(Source $source, FeedItem $item): bool
    {
        if (! $source->wants($item->title.' '.$item->description)) {
            return false;
        }

        $publishedAt = $item->publishedAt ? Carbon::instance($item->publishedAt) : Carbon::now();

        // withTrashed: a post we deleted (or a draft we rejected) is not imported again.
        $post = Post::withTrashed()->firstOrCreate(
            ['source_id' => $source->id, 'guid' => Str::limit($item->guid, 250, '')],
            [
                'category_id' => $source->importCategory()->id,
                'title' => Str::limit($item->title, 250),
                'excerpt' => self::excerpt($item),
                'original_url' => $item->link,
                'image_url' => $item->imageUrl,
                'language' => $source->language,
                // A feed item without a date is shown as published when we first saw it.
                // Items that need review stay drafts (no date) until someone publishes them.
                'published_at' => $source->needs_review ? null : $publishedAt,
                'source_published_at' => $publishedAt,
            ],
        );

        return $post->wasRecentlyCreated;
    }

    /**
     * The excerpt without the notices some channels put in every description, such as
     * Yonhap's "You can watch this video at … Copyright(C) Unauthorized use …" and hashtags.
     * Null when nothing else is left: the title then speaks for itself.
     */
    public static function excerpt(FeedItem $item): ?string
    {
        $text = $item->description !== '' ? $item->description : (string) $item->excerpt;

        $text = preg_replace([
            '/You can watch (?:this|the full) video (?:at|on) \\S+/iu',
            '/(?:Copyright\\s*\\(C\\)|ⓒ|©).*$/isu',
            '/(?:^|\\s)#[\\p{L}\\p{N}_]+/u',
        ], ' ', $text) ?? $text;

        $text = (string) preg_replace(['/\\s+/u', '/^[\\s\\-–—|·]+|[\\s\\-–—|·]+$/u'], [' ', ''], $text);

        if ($text === '') {
            return null;
        }

        return Str::limit($text, FeedParser::EXCERPT_LENGTH, '…', preserveWords: true);
    }
}
