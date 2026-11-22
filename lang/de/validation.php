<?php

// Only the rules used by sign-up, login and password forms.
// Any other rule falls back to Laravel's English message.

return [
    'required' => 'Das Feld :attribute ist erforderlich.',
    'string' => 'Das Feld :attribute muss ein Text sein.',
    'email' => 'Das Feld :attribute muss eine gültige E-Mail-Adresse sein.',
    'lowercase' => 'Das Feld :attribute muss kleingeschrieben sein.',
    'unique' => ':attribute ist bereits vergeben.',
    'confirmed' => 'Die Bestätigung von :attribute stimmt nicht überein.',
    'current_password' => 'Das Passwort ist falsch.',
    'max' => [
        'string' => 'Das Feld :attribute darf höchstens :max Zeichen haben.',
    ],
    'min' => [
        'string' => 'Das Feld :attribute muss mindestens :min Zeichen haben.',
    ],
    'password' => [
        'letters' => 'Das Feld :attribute muss mindestens einen Buchstaben enthalten.',
        'mixed' => 'Das Feld :attribute muss mindestens einen Groß- und einen Kleinbuchstaben enthalten.',
        'numbers' => 'Das Feld :attribute muss mindestens eine Zahl enthalten.',
        'symbols' => 'Das Feld :attribute muss mindestens ein Sonderzeichen enthalten.',
        'uncompromised' => 'Das angegebene :attribute ist in einem Datenleck aufgetaucht. Bitte wähle ein anderes :attribute.',
    ],
    'attributes' => [
        'current_password' => 'aktuelles Passwort',
        'name' => 'Name',
        'email' => 'E-Mail',
        'password' => 'Passwort',
        'recovery_code' => 'Wiederherstellungscode',
        'code' => 'Code',
    ],
];
