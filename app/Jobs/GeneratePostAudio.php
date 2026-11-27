<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\PostAudioService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GeneratePostAudio implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public function __construct(
        public int $postId,
        public string $locale,
    ) {}

    public function handle(PostAudioService $audio): void
    {
        $post = Post::withTrashed()->find($this->postId);

        if ($post === null || $post->trashed() || ! $post->isOriginalByNkLens()) {
            if ($post !== null) {
                $audio->clearPostAudio($post);
            }

            return;
        }

        $audio->generate($this->postId, $this->locale);
    }
}
