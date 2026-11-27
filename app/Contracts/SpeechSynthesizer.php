<?php

namespace App\Contracts;

interface SpeechSynthesizer
{
    public function synthesize(string $text, string $languageCode, string $voiceName): string;
}
