<?php

namespace Tests\Unit;

use App\Contracts\SpeechSynthesizer;
use App\Jobs\GeneratePostAudio;
use App\Models\Category;
use App\Models\Post;
use App\Models\Source;
use App\Services\PostAudioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class PostAudioServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_supported_locales_have_deterministic_google_cloud_voices(): void
    {
        $expected = [
            'en' => ['language_code' => 'en-US', 'name' => 'en-US-Neural2-F'],
            'ro' => ['language_code' => 'ro-RO', 'name' => 'ro-RO-Wavenet-A'],
            'ko' => ['language_code' => 'ko-KR', 'name' => 'ko-KR-Neural2-A'],
            'de' => ['language_code' => 'de-DE', 'name' => 'de-DE-Neural2-F'],
            'fr' => ['language_code' => 'fr-FR', 'name' => 'fr-FR-Neural2-A'],
            'es' => ['language_code' => 'es-ES', 'name' => 'es-ES-Neural2-A'],
            'it' => ['language_code' => 'it-IT', 'name' => 'it-IT-Neural2-A'],
            'pt' => ['language_code' => 'pt-BR', 'name' => 'pt-BR-Neural2-A'],
            'tr' => ['language_code' => 'tr-TR', 'name' => 'tr-TR-Wavenet-A'],
            'nb' => ['language_code' => 'nb-NO', 'name' => 'nb-NO-Wavenet-A'],
            'ru' => ['language_code' => 'ru-RU', 'name' => 'ru-RU-Wavenet-A'],
            'uk' => ['language_code' => 'uk-UA', 'name' => 'uk-UA-Wavenet-A'],
            'hu' => ['language_code' => 'hu-HU', 'name' => 'hu-HU-Wavenet-A'],
        ];

        $this->assertSame(array_keys(config('locales.supported')), array_keys($expected));
        $this->assertSame($expected, config('services.google_tts.voices'));
    }

    public function test_long_unicode_text_is_split_under_the_byte_limit_with_a_fixed_locale_voice(): void
    {
        Storage::fake('public');

        $post = $this->originalPost(
            '<p>'.str_repeat('가', 2000).'</p><script>must not be spoken</script>',
            'ko',
        );
        $calls = [];
        $synthesizer = Mockery::mock(SpeechSynthesizer::class);
        $synthesizer->shouldReceive('synthesize')
            ->andReturnUsing(function (string $text, string $languageCode, string $voiceName) use (&$calls): string {
                $calls[] = [$text, $languageCode, $voiceName];

                return "ID3\x04\x00\x00\x00\x00\x00\x03tag\xFF\xFBframe";
            });

        $service = new PostAudioService($synthesizer);
        $service->generate((int) $post->getKey(), 'ko');

        $this->assertGreaterThan(1, count($calls));

        foreach ($calls as [$text, $languageCode, $voiceName]) {
            $this->assertLessThanOrEqual(4500, strlen($text));
            $this->assertSame('ko-KR', $languageCode);
            $this->assertSame('ko-KR-Neural2-A', $voiceName);
            $this->assertStringNotContainsString('must not be spoken', $text);
        }

        $post->refresh();
        $this->assertNotNull($post->audio_generated_at);
        $this->assertSame(
            str_repeat("\xFF\xFBframe", count($calls)),
            Storage::disk('public')->get((string) $post->audio_path),
        );
    }

    public function test_audio_is_skipped_when_current_and_old_files_are_removed_after_regeneration(): void
    {
        Storage::fake('public');

        $post = $this->originalPost('First version.');
        $synthesizer = Mockery::mock(SpeechSynthesizer::class);
        $synthesizer->shouldReceive('synthesize')
            ->twice()
            ->andReturn('mp3-first', 'mp3-revised');

        $service = new PostAudioService($synthesizer);
        $service->generate((int) $post->getKey(), 'en');
        $post->refresh();
        $oldPath = (string) $post->audio_path;

        $service->generate((int) $post->getKey(), 'en');
        $this->assertTrue(Storage::disk('public')->exists($oldPath));

        $post->update(['body' => 'Revised version.']);
        $this->assertNull($service->audioUrlFor($post->fresh(), 'en'));

        $service->generate((int) $post->getKey(), 'en');
        $post->refresh();

        $this->assertNotSame($oldPath, $post->audio_path);
        $this->assertFalse(Storage::disk('public')->exists($oldPath));
        $this->assertNotNull($service->audioUrlFor($post, 'en'));
    }

    public function test_original_translation_uses_its_own_fixed_voice_and_audio_record(): void
    {
        Storage::fake('public');

        $post = $this->originalPost('Original body.');
        $translation = $post->translations()->create([
            'locale' => 'ro',
            'title' => 'Titlu <em>&amp;</em>',
            'body' => '<p>Text <strong>tradus</strong>.</p>',
        ]);
        $synthesizer = Mockery::mock(SpeechSynthesizer::class);
        $synthesizer->shouldReceive('synthesize')
            ->once()
            ->with('Titlu & Text tradus.', 'ro-RO', 'ro-RO-Wavenet-A')
            ->andReturn('mp3-romanian');

        (new PostAudioService($synthesizer))->generate((int) $post->getKey(), 'ro');

        $translation->refresh();
        $this->assertNotNull($translation->audio_path);
        $this->assertSame('mp3-romanian', Storage::disk('public')->get((string) $translation->audio_path));
    }

    public function test_changing_one_translation_only_invalidates_that_locales_audio(): void
    {
        Storage::fake('public');

        $post = $this->originalPost('English body.');
        $translation = $post->translations()->create([
            'locale' => 'ro',
            'title' => 'Titlu românesc',
            'body' => 'Text românesc.',
        ]);
        $synthesizer = Mockery::mock(SpeechSynthesizer::class);
        $synthesizer->shouldReceive('synthesize')
            ->times(3)
            ->andReturn('english-audio', 'romanian-audio-v1', 'romanian-audio-v2');
        $service = new PostAudioService($synthesizer);

        $service->generate((int) $post->getKey(), 'en');
        $service->generate((int) $post->getKey(), 'ro');

        $englishPath = $post->fresh()->audio_path;
        $oldRomanianPath = $translation->fresh()->audio_path;
        $translation->update(['body' => 'Text românesc actualizat.']);

        $this->assertNotNull($service->audioUrlFor($post->fresh(), 'en'));
        $this->assertNull($service->audioUrlFor($post->fresh(), 'ro'));

        $service->generate((int) $post->getKey(), 'ro');

        $this->assertSame($englishPath, $post->fresh()->audio_path);
        $this->assertNotSame($oldRomanianPath, $translation->fresh()->audio_path);
        $this->assertFalse(Storage::disk('public')->exists((string) $oldRomanianPath));
    }

    public function test_external_posts_cannot_generate_or_retain_audio(): void
    {
        Storage::fake('public');

        $post = Post::factory()
            ->for(Category::factory())
            ->for(Source::factory())
            ->create([
                'body' => 'Third-party text.',
                'published_at' => now()->subMinute(),
            ]);
        $path = 'post-audio/'.$post->id.'/en/external.mp3';
        Storage::disk('public')->put($path, 'old audio');
        $post->forceFill([
            'audio_path' => $path,
            'audio_hash' => str_repeat('a', 64),
            'audio_generated_at' => now(),
        ])->saveQuietly();
        $synthesizer = Mockery::mock(SpeechSynthesizer::class);
        $synthesizer->shouldNotReceive('synthesize');

        (new GeneratePostAudio((int) $post->getKey(), 'en'))->handle(new PostAudioService($synthesizer));

        $post->refresh();
        $this->assertNull($post->audio_path);
        $this->assertFalse(Storage::disk('public')->exists($path));
    }

    public function test_missing_translation_body_does_not_get_an_audio_record(): void
    {
        Storage::fake('public');

        $post = $this->originalPost('English body.');
        $translation = $post->translations()->create([
            'locale' => 'ro',
            'title' => 'Titlu tradus',
        ]);
        $synthesizer = Mockery::mock(SpeechSynthesizer::class);
        $synthesizer->shouldNotReceive('synthesize');

        (new PostAudioService($synthesizer))->generate((int) $post->getKey(), 'ro');

        $this->assertNull($translation->fresh()->audio_path);
    }

    public function test_deleting_a_post_removes_audio_for_it_and_its_translations(): void
    {
        Storage::fake('public');

        $post = $this->originalPost('English body.');
        $translation = $post->translations()->create([
            'locale' => 'ro',
            'title' => 'Titlu',
            'body' => 'Text.',
        ]);
        $synthesizer = Mockery::mock(SpeechSynthesizer::class);
        $synthesizer->shouldReceive('synthesize')->twice()->andReturn('english-audio', 'romanian-audio');
        $service = new PostAudioService($synthesizer);
        $service->generate((int) $post->getKey(), 'en');
        $service->generate((int) $post->getKey(), 'ro');

        $basePath = (string) $post->fresh()->audio_path;
        $translationPath = (string) $translation->fresh()->audio_path;

        $post->delete();

        $this->assertFalse(Storage::disk('public')->exists($basePath));
        $this->assertFalse(Storage::disk('public')->exists($translationPath));
        $this->assertNull(Post::withTrashed()->findOrFail($post->getKey())->audio_path);
        $this->assertNull($translation->fresh()->audio_path);
    }

    public function test_publishing_an_original_post_queues_audio_generation(): void
    {
        $post = $this->originalPost('Published article.');

        Queue::assertPushed(GeneratePostAudio::class, fn (GeneratePostAudio $job): bool => (
            $job->postId === $post->id && $job->locale === 'en'
        ));
    }

    private function originalPost(string $body, string $language = 'en'): Post
    {
        return Post::factory()
            ->ownContent()
            ->for(Category::factory())
            ->create([
                'title' => 'Article title',
                'body' => $body,
                'language' => $language,
                'published_at' => now()->subMinute(),
            ]);
    }
}
