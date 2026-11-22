<?php

// Only the rules used by sign-up, login and password forms.
// Any other rule falls back to Laravel's English message.

return [
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser un texto.',
    'email' => 'El campo :attribute debe ser un correo electrónico válido.',
    'lowercase' => 'El campo :attribute debe estar en minúsculas.',
    'unique' => 'Este :attribute ya está en uso.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'current_password' => 'La contraseña es incorrecta.',
    'max' => [
        'string' => 'El campo :attribute no puede tener más de :max caracteres.',
    ],
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'password' => [
        'letters' => 'El campo :attribute debe contener al menos una letra.',
        'mixed' => 'El campo :attribute debe contener al menos una mayúscula y una minúscula.',
        'numbers' => 'El campo :attribute debe contener al menos un número.',
        'symbols' => 'El campo :attribute debe contener al menos un símbolo.',
        'uncompromised' => 'Esta :attribute ha aparecido en una filtración de datos. Elige otra.',
    ],
    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'recovery_code' => 'código de recuperación',
        'code' => 'código',
    ],
];
