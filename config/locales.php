<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Interface languages
    |--------------------------------------------------------------------------
    |
    | Code => name written in that language, in the order the language picker
    | shows them. The interface strings live in lang/{code}.json and
    | lang/{code}/*.php; English is the source language and needs no JSON file.
    |
    */

    'supported' => [
        'en' => 'English',
        'ro' => 'Română',
        'ko' => '한국어',
        'de' => 'Deutsch',
        'fr' => 'Français',
        'es' => 'Español',
        'it' => 'Italiano',
        'pt' => 'Português',
        'tr' => 'Türkçe',
    ],

    /** Name of the cookie that remembers the visitor's choice. */
    'cookie' => 'locale',

];
