<?php

namespace Tests\Feature;

use App\Contracts\SpeechSynthesizer;
use App\Models\Category;
use App\Models\Post;
use App\Models\Source;
use App\Services\PostAudioService;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Tests\TestCase;

class PostPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CategorySeeder::class);
    }

    private function category(string $slug): Category
    {
        return Category::where('slug', $slug)->firstOrFail();
    }

    public function test_a_published_post_has_its_own_page(): void
    {
        $post = Post::factory()->for($this->category('news'))->create(['title' => 'Kim visits a shipyard']);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('post')
                ->where('post.id', $post->id)
                ->where('post.title', 'Kim visits a shipyard')
                ->where('body', null)
            );
    }

    public function test_source_logos_are_included_on_post_and_feed_pages(): void
    {
        $disk = Storage::fake('public');
        $logoPath = 'source-logos/daily-nk.png';
        $disk->put($logoPath, 'logo');
        $source = Source::factory()->create(['logo_path' => $logoPath]);
        $post = Post::factory()
            ->for($this->category('news'))
            ->for($source)
            ->create();

        $this->get(route('posts.show', $post))
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.source.logoUrl', $disk->url($logoPath))
            );

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('posts.0.source.logoUrl', $disk->url($logoPath))
            );
    }

    public function test_team_posts_show_their_full_text(): void
    {
        $post = Post::factory()
            ->ownContent()
            ->for($this->category('curiosities'))
            ->create(['body' => "First paragraph.\n\nSecond paragraph."]);

        $this->get(route('posts.show', $post))
            ->assertInertia(fn (Assert $page) => $page
                ->where('body', "First paragraph.\n\nSecond paragraph.")
                ->where('post.hasBody', true)
                ->where('post.source', null)
            );
    }

    public function test_original_editorial_sources_are_only_included_on_the_article_page(): void
    {
        $post = Post::factory()
            ->ownContent()
            ->for($this->category('news'))
            ->create([
                'editorial_note' => 'Sources: NK Archive and historical records.',
            ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('posts.0.editorialNote', null)
            );

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.editorialNote', 'Sources: NK Archive and historical records.')
            );
    }

    public function test_original_posts_expose_generated_audio(): void
    {
        $disk = Storage::fake('public');

        $post = Post::factory()
            ->ownContent()
            ->for($this->category('news'))
            ->create([
                'title' => 'Original article',
                'body' => 'Article body.',
            ]);
        $synthesizer = Mockery::mock(SpeechSynthesizer::class);
        $synthesizer->shouldReceive('synthesize')->once()->andReturn('mp3-content');

        app()->instance(SpeechSynthesizer::class, $synthesizer);
        app(PostAudioService::class)->generate((int) $post->getKey(), 'en');
        $audioUrl = $disk->url((string) $post->fresh()->audio_path);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('audioUrl', $audioUrl)
            );
    }

    public function test_external_posts_never_expose_audio(): void
    {
        Storage::fake('public');

        $post = Post::factory()
            ->for($this->category('news'))
            ->create();
        $path = 'post-audio/'.$post->id.'/en/external.mp3';
        Storage::disk('public')->put($path, 'third-party audio');
        $post->forceFill([
            'audio_path' => $path,
            'audio_hash' => str_repeat('a', 64),
            'audio_generated_at' => now(),
        ])->saveQuietly();

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('audioUrl', null)
            );
    }

    public function test_propaganda_shows_the_editorial_note(): void
    {
        $post = Post::factory()
            ->for($this->category('propaganda'))
            ->for(Source::factory()->stateMedia())
            ->create(['editorial_note' => 'Independent sources report food shortages in the region.']);

        $this->get(route('posts.show', $post))
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.isPropaganda', true)
                ->where('post.editorialNote', 'Independent sources report food shortages in the region.')
            );

        $this->get(route('home', ['category' => 'propaganda']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('posts.0.editorialNote', 'Independent sources report food shortages in the region.')
            );
    }

    public function test_drafts_are_not_public(): void
    {
        $post = Post::factory()->unpublished()->create();

        $this->get(route('posts.show', $post))->assertNotFound();
    }

    public function test_scheduled_posts_are_not_public_before_their_date(): void
    {
        $post = Post::factory()->create(['published_at' => now()->addDay()]);

        $this->get(route('posts.show', $post))->assertNotFound();
    }

    public function test_deleted_posts_are_gone(): void
    {
        $post = Post::factory()->create();
        $post->delete();

        $this->get(route('posts.show', $post))->assertNotFound();
    }
}
