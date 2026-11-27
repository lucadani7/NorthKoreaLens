<?php

namespace App\Feeds;

use App\Models\Post;
use App\Models\Source;
use App\Support\YouTube;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Stores feed items as posts of a source: only the ones that match its keywords,
 * in its section, and as drafts when the source needs review. Shorts are skipped when the
 * source asks for regular videos only.
 */
class FeedImporter
{
    /** @return bool True when the item became a new post. */
    public function import(Source $source, FeedItem $item): bool
    {
        if (! $source->wants($item->title.' '.$item->description)) {
            return false;
        }

        if ($source->skip_shorts && YouTube::isShort($item->link)) {
            return false;
        }

        // Live broadcasts (podcast streams, Q&A shows) are never imported from YouTube.
        if (YouTube::videoId($item->link) !== null && YouTube::isLivestreamTitle($item->title)) {
            return false;
        }

        $publishedAt = $item->publishedAt ? Carbon::instance($item->publishedAt) : Carbon::now();
        $searchTerms = implode(' ', array_unique(array_filter(array_map('trim', $item->categories))));
        $searchTerms = $searchTerms !== '' ? $searchTerms : null;

        $guid = Str::limit($item->guid, 250, '');
        // withTrashed: a post we deleted (or a draft we rejected) is not imported again.
        // RSS GUIDs and archive-page links can differ for the same Ghost article.
        $post = Post::withTrashed()
            ->where('source_id', $source->id)
            ->where(fn (Builder $query) => $query->where('guid', $guid)->orWhere('original_url', $item->link))
            ->orderBy('id')
            ->first();

        if ($post === null) {
            $post = Post::withTrashed()->firstOrCreate(
                ['source_id' => $source->id, 'guid' => $guid],
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
                    'search_terms' => $searchTerms,
                ],
            );
        }

        if (! $post->wasRecentlyCreated && $post->search_terms !== $searchTerms) {
            $post->forceFill(['search_terms' => $searchTerms])->save();
        }

        return $post->wasRecentlyCreated;
    }

    /**
     * A YouTuber's description without the calls to support, follow or email them and without
     * links: NK Lens does not promote anyone's Patreon, Instagram, TikTok, books or podcasts.
     */
    private static function withoutPromotion(string $text): string
    {
        return preg_replace([
            '~https?://\\S+~iu',
            '/[\\w.+-]+@[\\w-]+\\.[\\w.]+/u',
            '/Help me (?:be )?a voice for voiceless North Koreans[^.!?]*?Patreon[!.]?/iu',
            '/Pick up (?:some of )?(?:my|our|his|her) [^.!?:]{0,40}merch[^.!?:]*:?/iu',
            '/Want (?:Charles|us) to answer[^?]*\\?/iu',
            '/Email (?:him|us|me) at:?/iu',
            '/(?:Find|Follow) (?:her|him|me|us) on \\w+:?/iu',
            '/Check out [^:]{0,80}:/iu',
            '/[^.!?:]{0,80}\\bhere:/iu',
            '/To listen to the full conversation,? please visit/iu',
            '/Full episode available at[^.!?]*/iu',
            '/Here\'s the podcast I promised!?/iu',
        ], ' ', $text) ?? $text;
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

        // After the channel notices above, which need their links to be recognised.
        if (YouTube::videoId($item->link) !== null) {
            $text = self::withoutPromotion($text);
        }

        $text = (string) preg_replace(['/\\s+/u', '/^[\\s\\-–—|·]+|[\\s\\-–—|·]+$/u'], [' ', ''], $text);

        if ($text === '') {
            return null;
        }

        return Str::limit($text, FeedParser::EXCERPT_LENGTH, '…', preserveWords: true);
    }
}
