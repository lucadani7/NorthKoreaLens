<?php

// Only the rules used by sign-up, login and password forms.
// Any other rule falls back to Laravel's English message.

return [
    'required' => 'O campo :attribute é obrigatório.',
    'string' => 'O campo :attribute deve ser um texto.',
    'email' => 'O campo :attribute deve ser um e-mail válido.',
    'lowercase' => 'O campo :attribute deve estar em letras minúsculas.',
    'unique' => 'Este :attribute já está em uso.',
    'confirmed' => 'A confirmação de :attribute não corresponde.',
    'current_password' => 'A senha está incorreta.',
    'max' => [
        'string' => 'O campo :attribute não pode ter mais de :max caracteres.',
    ],
    'min' => [
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
    ],
    'password' => [
        'letters' => 'O campo :attribute deve conter pelo menos uma letra.',
        'mixed' => 'O campo :attribute deve conter pelo menos uma letra maiúscula e uma minúscula.',
        'numbers' => 'O campo :attribute deve conter pelo menos um número.',
        'symbols' => 'O campo :attribute deve conter pelo menos um símbolo.',
        'uncompromised' => 'Esta :attribute apareceu em um vazamento de dados. Escolha outra.',
    ],
    'attributes' => [
        'name' => 'nome',
        'email' => 'e-mail',
        'password' => 'senha',
        'recovery_code' => 'código de recuperação',
        'code' => 'código',
    ],
];
