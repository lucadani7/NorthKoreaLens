<?php

// Only the rules used by sign-up, login and password forms.
// Any other rule falls back to Laravel's English message.

return [
    'required' => 'Feltet :attribute må fylles ut.',
    'string' => 'Feltet :attribute må være tekst.',
    'email' => 'Feltet :attribute må være en gyldig e-postadresse.',
    'lowercase' => 'Feltet :attribute må skrives med små bokstaver.',
    'unique' => ':attribute er allerede i bruk.',
    'confirmed' => 'Bekreftelsen av :attribute stemmer ikke.',
    'current_password' => 'Passordet er feil.',
    'max' => [
        'string' => 'Feltet :attribute kan ikke ha mer enn :max tegn.',
    ],
    'min' => [
        'string' => 'Feltet :attribute må ha minst :min tegn.',
    ],
    'password' => [
        'letters' => 'Feltet :attribute må inneholde minst én bokstav.',
        'mixed' => 'Feltet :attribute må inneholde minst én stor og én liten bokstav.',
        'numbers' => 'Feltet :attribute må inneholde minst ett tall.',
        'symbols' => 'Feltet :attribute må inneholde minst ett symbol.',
        'uncompromised' => ':attribute har dukket opp i en datalekkasje. Velg et annet :attribute.',
    ],
    'attributes' => [
        'current_password' => 'nåværende passord',
        'name' => 'navn',
        'email' => 'e-post',
        'password' => 'passord',
        'recovery_code' => 'gjenopprettingskode',
        'code' => 'kode',
    ],
];
