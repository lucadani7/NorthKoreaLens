<?php

namespace App\Console\Commands;

use App\Jobs\GeneratePostAudio;
use App\Models\Post;
use Illuminate\Console\Command;

class GeneratePostAudioCommand extends Command
{
    protected $signature = 'posts:generate-audio {--post= : Limit generation to one post ID}';

    protected $description = 'Queue audio generation for published original NK Lens articles';

    public function handle(): int
    {
        $postId = $this->option('post');
        $validatedPostId = $postId === null ? null : filter_var($postId, FILTER_VALIDATE_INT);

        if ($postId !== null && (! is_int($validatedPostId) || $validatedPostId < 1)) {
            $this->error('The --post option must be a positive post ID.');

            return self::FAILURE;
        }

        $supportedLocales = config('locales.supported', []);
        $queued = 0;
        $posts = Post::query()
            ->where('is_nklens_original', true)
            ->whereNull('source_id')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with('translations');

        if ($validatedPostId !== null) {
            $posts->whereKey($validatedPostId);
        }

        $posts->chunkById(100, function ($batch) use ($supportedLocales, &$queued): void {
            foreach ($batch as $post) {
                if (filled($post->body) && array_key_exists($post->language, $supportedLocales)) {
                    dispatch(new GeneratePostAudio((int) $post->getKey(), $post->language));
                    $queued++;
                }

                foreach ($post->translations as $translation) {
                    if (filled($translation->body) && array_key_exists($translation->locale, $supportedLocales)) {
                        dispatch(new GeneratePostAudio((int) $post->getKey(), $translation->locale));
                        $queued++;
                    }
                }
            }
        });

        $this->info("Queued {$queued} post audio generation job(s).");

        return self::SUCCESS;
    }
}
