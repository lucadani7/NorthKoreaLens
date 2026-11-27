<?php

namespace App\Services;

use App\Contracts\SpeechSynthesizer;
use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\Client\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\SynthesizeSpeechRequest;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;

class GoogleCloudSpeechSynthesizer implements SpeechSynthesizer
{
    public function synthesize(string $text, string $languageCode, string $voiceName): string
    {
        $credentials = config('services.google_tts.credentials');
        $options = is_string($credentials) && $credentials !== ''
            ? ['keyFilePath' => $credentials]
            : [];
        $client = new TextToSpeechClient($options);

        try {
            $response = $client->synthesizeSpeech(new SynthesizeSpeechRequest([
                'input' => new SynthesisInput(['text' => $text]),
                'voice' => new VoiceSelectionParams([
                    'language_code' => $languageCode,
                    'name' => $voiceName,
                ]),
                'audio_config' => new AudioConfig([
                    'audio_encoding' => AudioEncoding::MP3,
                ]),
            ]));

            return $response->getAudioContent();
        } finally {
            $client->close();
        }
    }
}
