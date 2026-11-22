<?php

// Only the rules used by sign-up, login and password forms.
// Any other rule falls back to Laravel's English message.

return [
    'required' => 'Il campo :attribute è obbligatorio.',
    'string' => 'Il campo :attribute deve essere un testo.',
    'email' => 'Il campo :attribute deve essere un indirizzo email valido.',
    'lowercase' => 'Il campo :attribute deve essere in minuscolo.',
    'unique' => 'Questo :attribute è già in uso.',
    'confirmed' => 'La conferma di :attribute non corrisponde.',
    'current_password' => 'La password non è corretta.',
    'max' => [
        'string' => 'Il campo :attribute non può superare :max caratteri.',
    ],
    'min' => [
        'string' => 'Il campo :attribute deve contenere almeno :min caratteri.',
    ],
    'password' => [
        'letters' => 'Il campo :attribute deve contenere almeno una lettera.',
        'mixed' => 'Il campo :attribute deve contenere almeno una lettera maiuscola e una minuscola.',
        'numbers' => 'Il campo :attribute deve contenere almeno un numero.',
        'symbols' => 'Il campo :attribute deve contenere almeno un simbolo.',
        'uncompromised' => 'Questa :attribute è comparsa in una fuga di dati. Scegline un’altra.',
    ],
    'attributes' => [
        'current_password' => 'password attuale',
        'name' => 'nome',
        'email' => 'email',
        'password' => 'password',
        'recovery_code' => 'codice di recupero',
        'code' => 'codice',
    ],
];
