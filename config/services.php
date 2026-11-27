<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // YouTube Data API v3, for searching channel archives (php artisan youtube:backfill).
    'youtube' => [
        'key' => env('YOUTUBE_API_KEY'),
    ],

    'google_tts' => [
        'credentials' => env('GOOGLE_APPLICATION_CREDENTIALS'),
        'disk' => env('GOOGLE_TTS_DISK', 'public'),
        'voices' => [
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
        ],
    ],

];
