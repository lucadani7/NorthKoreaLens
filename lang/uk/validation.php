<?php

// Only the rules used by sign-up, login and password forms.
// Any other rule falls back to Laravel's English message.

return [
    'required' => 'Поле «:attribute» обов’язкове.',
    'string' => 'Поле «:attribute» має бути текстом.',
    'email' => 'Поле «:attribute» має бути дійсною адресою ел. пошти.',
    'lowercase' => 'Поле «:attribute» має бути написане малими літерами.',
    'unique' => 'Таке значення поля «:attribute» уже використовується.',
    'confirmed' => 'Підтвердження поля «:attribute» не збігається.',
    'current_password' => 'Неправильний пароль.',
    'max' => [
        'string' => 'Поле «:attribute» не може бути довшим за :max символів.',
    ],
    'min' => [
        'string' => 'Поле «:attribute» має містити щонайменше :min символів.',
    ],
    'password' => [
        'letters' => 'Поле «:attribute» має містити щонайменше одну літеру.',
        'mixed' => 'Поле «:attribute» має містити щонайменше одну велику й одну малу літеру.',
        'numbers' => 'Поле «:attribute» має містити щонайменше одну цифру.',
        'symbols' => 'Поле «:attribute» має містити щонайменше один символ.',
        'uncompromised' => 'Таке значення поля «:attribute» траплялося у витоку даних. Оберіть інше.',
    ],
    'attributes' => [
        'current_password' => 'поточний пароль',
        'name' => 'ім’я',
        'email' => 'ел. пошта',
        'password' => 'пароль',
        'recovery_code' => 'код відновлення',
        'code' => 'код',
    ],
];
