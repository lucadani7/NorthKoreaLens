<?php

namespace App\Services;

use App\Contracts\SpeechSynthesizer;
use App\Models\Post;
use App\Models\PostTranslation;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class PostAudioService
{
    private const MAX_REQUEST_BYTES = 4500;

    public function __construct(private readonly SpeechSynthesizer $synthesizer) {}

    public function generate(int $postId, string $locale): void
    {
        $post = Post::withTrashed()->with('translations')->find($postId);

        if ($post === null) {
            return;
        }

        if ($post->trashed() || ! $post->isOriginalByNkLens()) {
            $this->clearPostAudio($post);

            return;
        }

        if (! $post->isPublished()) {
            return;
        }

        $record = $this->recordFor($post, $locale);

        if ($record === null) {
            return;
        }

        $payload = $this->payloadFor($post, $locale, true);

        if ($payload === null) {
            $this->clearRecordAudio($record);

            return;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($this->diskName());

        if (
            $record->audio_hash === $payload['hash']
            && filled($record->audio_path)
            && $disk->exists($record->audio_path)
        ) {
            return;
        }

        $segments = [];

        foreach ($this->splitText($payload['text']) as $chunk) {
            $audio = $this->synthesizer->synthesize(
                $chunk,
                $payload['language_code'],
                $payload['voice_name'],
            );
            $audio = $this->stripMp3Metadata($audio);

            if ($audio === '') {
                throw new RuntimeException("Text-to-speech returned empty audio for post {$postId} ({$locale}).");
            }

            $segments[] = $audio;
        }

        $audio = implode('', $segments);
        $path = sprintf('post-audio/%d/%s/%s.mp3', $post->getKey(), $locale, $payload['hash']);

        if (! $disk->put($path, $audio)) {
            throw new RuntimeException("Unable to store generated audio for post {$postId} ({$locale}).");
        }

        $oldPath = $record->audio_path;

        try {
            $saved = $record->forceFill([
                'audio_path' => $path,
                'audio_hash' => $payload['hash'],
                'audio_generated_at' => now(),
            ])->saveQuietly();

            if (! $saved) {
                throw new RuntimeException("Unable to save generated audio metadata for post {$postId} ({$locale}).");
            }
        } catch (Throwable $exception) {
            $this->deletePath($path);

            throw $exception;
        }

        if ($oldPath !== null && $oldPath !== $path) {
            $this->deletePath($oldPath);
        }
    }

    public function audioUrlFor(Post $post, string $locale): ?string
    {
        if (! $post->isOriginalByNkLens() || ! $post->isPublished()) {
            return null;
        }

        $payload = $this->payloadFor($post, $locale, false);

        if ($payload === null) {
            return null;
        }

        $record = $payload['record'];
        $path = $record->audio_path;

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($this->diskName());

        if (
            $record->audio_hash !== $payload['hash']
            || ! is_string($path)
            || ! $disk->exists($path)
        ) {
            return null;
        }

        return $disk->url($path);
    }

    public function clearPostAudio(Post $post): void
    {
        $this->clearRecordAudio($post);

        foreach ($post->translations()->get() as $translation) {
            $this->clearRecordAudio($translation);
        }
    }

    public function clearPostBaseAudio(Post $post): void
    {
        $this->clearRecordAudio($post);
    }

    public function clearTranslationAudio(PostTranslation $translation): void
    {
        $this->clearRecordAudio($translation);
    }

    /**
     * @return list<string>
     */
    public function splitText(string $text): array
    {
        $sentences = preg_split('/(?<=[.!?…。！？])\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);

        if (! is_array($sentences)) {
            $sentences = [trim($text)];
        }

        $chunks = [];
        $current = '';

        foreach ($sentences as $sentence) {
            $sentenceChunks = strlen($sentence) <= self::MAX_REQUEST_BYTES
                ? [$sentence]
                : $this->splitLongSentence($sentence);

            foreach ($sentenceChunks as $sentenceChunk) {
                $candidate = $current === '' ? $sentenceChunk : $current.' '.$sentenceChunk;

                if (strlen($candidate) > self::MAX_REQUEST_BYTES && $current !== '') {
                    $chunks[] = $current;
                    $current = $sentenceChunk;
                } else {
                    $current = $candidate;
                }
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }

    /**
     * @return array{
     *     record: Post|PostTranslation,
     *     text: string,
     *     language_code: string,
     *     voice_name: string,
     *     hash: string
     * }|null
     */
    private function payloadFor(Post $post, string $locale, bool $strict): ?array
    {
        $record = $this->recordFor($post, $locale);

        if ($record === null || ! filled($record->title) || ! filled($record->body)) {
            return null;
        }

        $supported = config('locales.supported', []);
        $voices = config('services.google_tts.voices', []);
        $voice = $voices[$locale] ?? null;

        if (
            ! is_array($supported)
            || ! array_key_exists($locale, $supported)
            || ! is_array($voice)
            || ! is_string($voice['language_code'] ?? null)
            || ! is_string($voice['name'] ?? null)
        ) {
            if ($strict) {
                throw new InvalidArgumentException("No fixed Google Cloud TTS voice is configured for locale [{$locale}].");
            }

            return null;
        }

        $text = $this->plainText($record->title."\n".(string) $record->body);

        if ($text === '') {
            return null;
        }

        return [
            'record' => $record,
            'text' => $text,
            'language_code' => $voice['language_code'],
            'voice_name' => $voice['name'],
            'hash' => hash('sha256', $locale."\0".$voice['language_code']."\0".$voice['name']."\0".$text),
        ];
    }

    private function recordFor(Post $post, string $locale): Post|PostTranslation|null
    {
        return $locale === $post->language
            ? $post
            : $post->translationFor($locale);
    }

    private function plainText(string $content): string
    {
        $content = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#isu', ' ', $content) ?? $content;
        $content = preg_replace('/<\s*br\b[^>]*>|<\/(?:p|div|li|h[1-6]|section|article)\s*>/i', ' ', $content) ?? $content;
        $content = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $content = preg_replace('/[\p{Z}\s]+/u', ' ', $content) ?? $content;

        return trim($content);
    }

    /**
     * @return list<string>
     */
    private function splitLongSentence(string $sentence): array
    {
        $words = preg_split('/\s+/u', trim($sentence), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $chunks = [];
        $current = '';

        foreach ($words as $word) {
            if (strlen($word) > self::MAX_REQUEST_BYTES) {
                if ($current !== '') {
                    $chunks[] = $current;
                    $current = '';
                }

                $characters = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: str_split($word);

                foreach ($characters as $character) {
                    if ($current !== '' && strlen($current.$character) > self::MAX_REQUEST_BYTES) {
                        $chunks[] = $current;
                        $current = '';
                    }

                    $current .= $character;
                }

                continue;
            }

            $candidate = $current === '' ? $word : $current.' '.$word;

            if (strlen($candidate) > self::MAX_REQUEST_BYTES && $current !== '') {
                $chunks[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }

    private function stripMp3Metadata(string $audio): string
    {
        if (strlen($audio) >= 10 && substr($audio, 0, 3) === 'ID3') {
            $size = ((ord($audio[6]) & 0x7F) << 21)
                | ((ord($audio[7]) & 0x7F) << 14)
                | ((ord($audio[8]) & 0x7F) << 7)
                | (ord($audio[9]) & 0x7F);
            $offset = 10 + $size + ((ord($audio[5]) & 0x10) !== 0 ? 10 : 0);
            $audio = substr($audio, min($offset, strlen($audio)));
        }

        if (strlen($audio) >= 128 && substr($audio, -128, 3) === 'TAG') {
            $audio = substr($audio, 0, -128);
        }

        return $audio;
    }

    private function clearRecordAudio(Post|PostTranslation $record): void
    {
        $record->refresh();
        $path = $record->audio_path;

        if ($path !== null) {
            $this->deletePath($path);
        }

        if ($record->audio_path !== null || $record->audio_hash !== null || $record->audio_generated_at !== null) {
            $saved = $record->forceFill([
                'audio_path' => null,
                'audio_hash' => null,
                'audio_generated_at' => null,
            ])->saveQuietly();

            if (! $saved) {
                throw new RuntimeException('Unable to clear generated audio metadata.');
            }
        }
    }

    private function deletePath(string $path): void
    {
        $disk = Storage::disk($this->diskName());

        if ($disk->exists($path) && ! $disk->delete($path)) {
            throw new RuntimeException("Unable to delete generated audio at [{$path}].");
        }
    }

    private function diskName(): string
    {
        $disk = config('services.google_tts.disk');

        if (! is_string($disk) || $disk === '') {
            throw new InvalidArgumentException('Google Cloud TTS storage disk is not configured.');
        }

        return $disk;
    }
}
