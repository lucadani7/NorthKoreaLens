<?php

// Only the rules used by sign-up, login and password forms.
// Any other rule falls back to Laravel's English message.

return [
    'required' => 'Поле «:attribute» обязательно.',
    'string' => 'Поле «:attribute» должно быть текстом.',
    'email' => 'Поле «:attribute» должно быть действительным адресом эл. почты.',
    'lowercase' => 'Поле «:attribute» должно быть написано строчными буквами.',
    'unique' => 'Такое значение поля «:attribute» уже используется.',
    'confirmed' => 'Подтверждение поля «:attribute» не совпадает.',
    'current_password' => 'Неверный пароль.',
    'max' => [
        'string' => 'Поле «:attribute» не может быть длиннее :max символов.',
    ],
    'min' => [
        'string' => 'Поле «:attribute» должно содержать не менее :min символов.',
    ],
    'password' => [
        'letters' => 'Поле «:attribute» должно содержать хотя бы одну букву.',
        'mixed' => 'Поле «:attribute» должно содержать хотя бы одну заглавную и одну строчную букву.',
        'numbers' => 'Поле «:attribute» должно содержать хотя бы одну цифру.',
        'symbols' => 'Поле «:attribute» должно содержать хотя бы один символ.',
        'uncompromised' => 'Такое значение поля «:attribute» встречалось в утечке данных. Выберите другое.',
    ],
    'attributes' => [
        'current_password' => 'текущий пароль',
        'name' => 'имя',
        'email' => 'эл. почта',
        'password' => 'пароль',
        'recovery_code' => 'код восстановления',
        'code' => 'код',
    ],
];
