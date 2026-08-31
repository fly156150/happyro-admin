<?php

return ['players' => [
    'account_table' => env('GAME_ACCOUNT_TABLE', 'login'),
    'character_table' => env('GAME_CHARACTER_TABLE', 'char'),
    'login_log_table' => env('GAME_LOGIN_LOG_TABLE', 'loginlog'),
    'password_hash' => env('GAME_PASSWORD_HASH', false),
]];
