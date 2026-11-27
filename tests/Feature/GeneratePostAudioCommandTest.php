<?php

namespace Tests\Feature;

use App\Jobs\GeneratePostAudio;
use App\Models\Category;
use App\Models\Post;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GeneratePostAudioCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_queues_audio_only_for_published_original_text(): void
    {
        $original = Post::factory()
            ->ownContent()
            ->for(Category::factory())
            ->create([
                'language' => 'en',
                'body' => 'Original body.',
                'published_at' => now()->subMinute(),
            ]);
        $original->translations()->create([
            'locale' => 'ro',
            'title' => 'Titlu',
            'body' => 'Text tradus.',
        ]);
        Post::factory()
            ->for(Category::factory())
            ->for(Source::factory())
            ->create(['body' => 'External body.']);
        Post::factory()
            ->ownContent()
            ->for(Category::factory())
            ->unpublished()
            ->create();
        Queue::fake();

        $this->artisan('posts:generate-audio')
            ->expectsOutput('Queued 2 post audio generation job(s).')
            ->assertExitCode(0);

        Queue::assertPushed(GeneratePostAudio::class, 2);
        Queue::assertPushed(
            GeneratePostAudio::class,
            fn (GeneratePostAudio $job): bool => $job->postId === $original->id && $job->locale === 'en',
        );
        Queue::assertPushed(
            GeneratePostAudio::class,
            fn (GeneratePostAudio $job): bool => $job->postId === $original->id && $job->locale === 'ro',
        );
    }

    public function test_command_rejects_an_invalid_post_id(): void
    {
        $this->artisan('posts:generate-audio', ['--post' => 'not-an-id'])
            ->expectsOutput('The --post option must be a positive post ID.')
            ->assertExitCode(1);
    }
}
