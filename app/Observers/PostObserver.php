<?php

namespace App\Observers;

use App\Jobs\GeneratePostAudio;
use App\Models\Post;
use App\Services\PostAudioService;

class PostObserver
{
    public function saved(Post $post): void
    {
        if (! $post->wasRecentlyCreated && ! $post->wasChanged(['title', 'body', 'language', 'published_at', 'source_id'])) {
            return;
        }

        $this->dispatchAudioForPost($post);
    }

    public function deleting(Post $post): void
    {
        app(PostAudioService::class)->clearPostAudio($post);
    }

    public function restored(Post $post): void
    {
        $this->dispatchAudioForPost($post);
    }

    private function dispatchAudioForPost(Post $post): void
    {
        $audio = app(PostAudioService::class);

        if (! $post->isOriginalByNkLens()) {
            $audio->clearPostAudio($post);

            return;
        }

        if ($post->published_at === null) {
            $audio->clearPostAudio($post);

            return;
        }

        if (filled($post->body)) {
            $this->dispatch($post, $post->language);
        } else {
            $audio->clearPostBaseAudio($post);
        }

        foreach ($post->translations()->whereNotNull('body')->where('body', '<>', '')->get() as $translation) {
            if (array_key_exists($translation->locale, config('locales.supported', []))) {
                $this->dispatch($post, $translation->locale);
            }
        }
    }

    private function dispatch(Post $post, string $locale): void
    {
        if (! array_key_exists($locale, config('locales.supported', []))) {
            return;
        }

        $job = (new GeneratePostAudio((int) $post->getKey(), $locale))->afterCommit();

        if ($post->published_at?->isFuture()) {
            $job->delay($post->published_at);
        }

        dispatch($job);
    }
}
