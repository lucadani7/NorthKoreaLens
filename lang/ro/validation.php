<?php

// Only the rules used by sign-up, login and password forms.
// Any other rule falls back to Laravel's English message.

return [
    'required' => 'Câmpul :attribute este obligatoriu.',
    'string' => 'Câmpul :attribute trebuie să fie text.',
    'email' => 'Câmpul :attribute trebuie să fie o adresă de email validă.',
    'lowercase' => 'Câmpul :attribute trebuie să fie scris cu litere mici.',
    'unique' => ':attribute este deja folosit.',
    'confirmed' => 'Confirmarea pentru :attribute nu se potrivește.',
    'current_password' => 'Parola este greșită.',
    'max' => [
        'string' => 'Câmpul :attribute nu poate avea mai mult de :max caractere.',
    ],
    'min' => [
        'string' => 'Câmpul :attribute trebuie să aibă cel puțin :min caractere.',
    ],
    'password' => [
        'letters' => 'Câmpul :attribute trebuie să conțină cel puțin o literă.',
        'mixed' => 'Câmpul :attribute trebuie să conțină cel puțin o literă mare și una mică.',
        'numbers' => 'Câmpul :attribute trebuie să conțină cel puțin o cifră.',
        'symbols' => 'Câmpul :attribute trebuie să conțină cel puțin un simbol.',
        'uncompromised' => ':attribute a apărut într-o scurgere de date. Alege altă :attribute.',
    ],
    'attributes' => [
        'current_password' => 'parola actuală',
        'name' => 'nume',
        'email' => 'email',
        'password' => 'parola',
        'recovery_code' => 'codul de recuperare',
        'code' => 'codul',
    ],
];
