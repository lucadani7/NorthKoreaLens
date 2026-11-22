<?php

// Only the rules used by sign-up, login and password forms.
// Any other rule falls back to Laravel's English message.

return [
    'required' => ':attribute alanı zorunludur.',
    'string' => ':attribute alanı metin olmalıdır.',
    'email' => ':attribute alanı geçerli bir e-posta adresi olmalıdır.',
    'lowercase' => ':attribute alanı küçük harf olmalıdır.',
    'unique' => 'Bu :attribute zaten kullanılıyor.',
    'confirmed' => ':attribute doğrulaması eşleşmiyor.',
    'current_password' => 'Şifre yanlış.',
    'max' => [
        'string' => ':attribute alanı en fazla :max karakter olabilir.',
    ],
    'min' => [
        'string' => ':attribute alanı en az :min karakter olmalıdır.',
    ],
    'password' => [
        'letters' => ':attribute alanı en az bir harf içermelidir.',
        'mixed' => ':attribute alanı en az bir büyük ve bir küçük harf içermelidir.',
        'numbers' => ':attribute alanı en az bir rakam içermelidir.',
        'symbols' => ':attribute alanı en az bir sembol içermelidir.',
        'uncompromised' => 'Bu :attribute bir veri sızıntısında görüldü. Lütfen başka bir :attribute seç.',
    ],
    'attributes' => [
        'current_password' => 'mevcut şifre',
        'name' => 'ad',
        'email' => 'e-posta',
        'password' => 'şifre',
        'recovery_code' => 'kurtarma kodu',
        'code' => 'kod',
    ],
];
