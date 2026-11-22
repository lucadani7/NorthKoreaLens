<?php

// Only the rules used by sign-up, login and password forms.
// Any other rule falls back to Laravel's English message.

return [
    'required' => ':attribute 항목은 필수입니다.',
    'string' => ':attribute 항목은 문자열이어야 합니다.',
    'email' => ':attribute 항목은 올바른 이메일 주소여야 합니다.',
    'lowercase' => ':attribute 항목은 소문자여야 합니다.',
    'unique' => '이미 사용 중인 :attribute입니다.',
    'confirmed' => ':attribute 확인 값이 일치하지 않습니다.',
    'current_password' => '비밀번호가 올바르지 않습니다.',
    'max' => [
        'string' => ':attribute 항목은 :max자를 넘을 수 없습니다.',
    ],
    'min' => [
        'string' => ':attribute 항목은 최소 :min자 이상이어야 합니다.',
    ],
    'password' => [
        'letters' => ':attribute 항목에는 문자가 하나 이상 있어야 합니다.',
        'mixed' => ':attribute 항목에는 대문자와 소문자가 각각 하나 이상 있어야 합니다.',
        'numbers' => ':attribute 항목에는 숫자가 하나 이상 있어야 합니다.',
        'symbols' => ':attribute 항목에는 기호가 하나 이상 있어야 합니다.',
        'uncompromised' => '입력한 :attribute이(가) 데이터 유출에서 발견되었습니다. 다른 :attribute을(를) 선택하세요.',
    ],
    'attributes' => [
        'name' => '이름',
        'email' => '이메일',
        'password' => '비밀번호',
        'recovery_code' => '복구 코드',
        'code' => '코드',
    ],
];
