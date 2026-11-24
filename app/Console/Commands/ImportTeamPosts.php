<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use JsonException;

/**
 * Loads team posts (e.g. a batch of curiosities) from a JSON file, in every language at once.
 * New posts are drafts; running it again updates their text but never publishes or unpublishes.
 *
 * {"posts": [{"key": "seoul-first-capital", "category": "curiosities", "topics": ["history"], "original_url": null,
 *   "image": {"src": "/images/curiosities/seoul.jpg", "credit": "Photo: …, CC BY-SA 4.0, via Wikimedia Commons"},
 *   "en": {"title": "…", "excerpt": "…", "body": "…", "editorial_note": "Sources: …"},
 *   "ro": {"title": "…", …}, …}]}
 */
class ImportTeamPosts extends Command
{
    protected $signature = 'posts:import {file : Path to the JSON file}';

    protected $description = 'Import team posts (such as curiosities) in every language, as drafts';

    public function handle(): int
    {
        $path = (string) $this->argument('file');

        if (! is_file($path)) {
            $this->error("No file at {$path}.");

            return self::FAILURE;
        }

        try {
            /** @var array{posts?: list<array<string, mixed>>} $data */
            $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->error('The file is not valid JSON: '.$e->getMessage());

            return self::FAILURE;
        }

        $locales = array_keys(config('locales.supported'));
        $created = 0;
        $updated = 0;

        foreach ($data['posts'] ?? [] as $entry) {
            $key = $entry['key'] ?? null;
            $english = $entry['en'] ?? null;
            $category = Category::where('slug', $entry['category'] ?? '')->first();

            if (! is_string($key) || ! is_array($english) || blank($english['title'] ?? null) || $category === null) {
                $this->warn('Skipped an entry without a key, a known category or an English title.');

                continue;
            }

            DB::transaction(function () use ($entry, $key, $english, $category, $locales, &$created, &$updated) {
                $post = Post::withTrashed()->firstOrNew(['source_id' => null, 'guid' => "nklens:{$key}"]);
                $post->exists ? $updated++ : $created++;

                $post->fill([
                    'category_id' => $category->id,
                    'title' => $english['title'],
                    'excerpt' => $english['excerpt'] ?? null,
                    'body' => $english['body'] ?? null,
                    'editorial_note' => $english['editorial_note'] ?? null,
                    // A verse or quote shown above the text, the same in every language.
                    'epigraph' => $entry['epigraph'] ?? null,
                    'language' => 'en',
                    // A YouTube link here is played on the post page.
                    'original_url' => $entry['original_url'] ?? null,
                ]);

                // Only freely licensed photos, stored under public/images and credited under them.
                // An entry without "image" leaves the post's image as it is; "image": null removes it.
                if (array_key_exists('image', $entry)) {
                    $image = is_array($entry['image']) ? $entry['image'] : [];
                    $post->image_url = is_string($image['src'] ?? null) ? $image['src'] : null;
                    $post->image_credit = is_string($image['credit'] ?? null) ? $image['credit'] : null;
                }

                $post->save();

                $topics = Topic::whereIn('slug', (array) ($entry['topics'] ?? []))->pluck('id');
                $post->topics()->sync($topics);

                foreach ($locales as $locale) {
                    $text = $entry[$locale] ?? null;

                    if ($locale === 'en' || ! is_array($text) || blank($text['title'] ?? null)) {
                        continue;
                    }

                    $post->translations()->updateOrCreate(['locale' => $locale], [
                        'title' => $text['title'],
                        'excerpt' => $text['excerpt'] ?? null,
                        'body' => $text['body'] ?? null,
                        'editorial_note' => $text['editorial_note'] ?? null,
                    ]);
                }
            });
        }

        $this->info("{$created} new drafts, {$updated} updated. Review and publish them in /admin.");

        return self::SUCCESS;
    }
}
