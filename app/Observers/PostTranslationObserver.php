<?php

namespace App\Observers;

use App\Jobs\GeneratePostAudio;
use App\Models\PostTranslation;
use App\Services\PostAudioService;

class PostTranslationObserver
{
    public function saved(PostTranslation $translation): void
    {
        if (! $translation->wasRecentlyCreated && ! $translation->wasChanged(['title', 'body', 'locale'])) {
            return;
        }

        $post = $translation->post;

        if ($post === null) {
            return;
        }

        if (! $post->isOriginalByNkLens()) {
            app(PostAudioService::class)->clearTranslationAudio($translation);

            return;
        }

        if (! filled($translation->body)) {
            app(PostAudioService::class)->clearTranslationAudio($translation);

            return;
        }

        if ($post->published_at === null || ! array_key_exists($translation->locale, config('locales.supported', []))) {
            app(PostAudioService::class)->clearTranslationAudio($translation);

            return;
        }

        $job = (new GeneratePostAudio((int) $post->getKey(), $translation->locale))->afterCommit();

        if ($post->published_at->isFuture()) {
            $job->delay($post->published_at);
        }

        dispatch($job);
    }

    public function deleting(PostTranslation $translation): void
    {
        app(PostAudioService::class)->clearTranslationAudio($translation);
    }
}
