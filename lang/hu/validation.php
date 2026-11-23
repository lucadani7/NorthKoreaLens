<?php

// Only the rules used by sign-up, login and password forms.
// Any other rule falls back to Laravel's English message.

return [
    'required' => 'A(z) :attribute mező kitöltése kötelező.',
    'string' => 'A(z) :attribute mezőnek szövegnek kell lennie.',
    'email' => 'A(z) :attribute mezőnek érvényes e-mail-címnek kell lennie.',
    'lowercase' => 'A(z) :attribute mezőt kisbetűkkel kell írni.',
    'unique' => 'Ez a(z) :attribute már foglalt.',
    'confirmed' => 'A(z) :attribute megerősítése nem egyezik.',
    'current_password' => 'A jelszó helytelen.',
    'max' => [
        'string' => 'A(z) :attribute legfeljebb :max karakter lehet.',
    ],
    'min' => [
        'string' => 'A(z) :attribute legalább :min karakter legyen.',
    ],
    'password' => [
        'letters' => 'A(z) :attribute tartalmazzon legalább egy betűt.',
        'mixed' => 'A(z) :attribute tartalmazzon legalább egy nagy- és egy kisbetűt.',
        'numbers' => 'A(z) :attribute tartalmazzon legalább egy számjegyet.',
        'symbols' => 'A(z) :attribute tartalmazzon legalább egy szimbólumot.',
        'uncompromised' => 'Ez a(z) :attribute szerepelt egy adatszivárgásban. Válassz másikat.',
    ],
    'attributes' => [
        'current_password' => 'jelenlegi jelszó',
        'name' => 'név',
        'email' => 'e-mail-cím',
        'password' => 'jelszó',
        'recovery_code' => 'helyreállító kód',
        'code' => 'kód',
    ],
];
