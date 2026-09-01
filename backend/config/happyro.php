<?php

return ['players' => [
    'account_table' => env('GAME_ACCOUNT_TABLE', 'login'),
    'character_table' => env('GAME_CHARACTER_TABLE', 'char'),
    'login_log_table' => env('GAME_LOGIN_LOG_TABLE', 'loginlog'),
    'password_hash' => env('GAME_PASSWORD_HASH', false),
], 'resources' => [
    'item_catalog' => base_path('resources/game/items/renewal.json'),
    'item_icon_map' => base_path('resources/game/items/icon-map.json'),
    'item_descriptions' => base_path('resources/game/items/descriptions.json'),
    'grf_root' => env('GAME_RESOURCE_ROOT', base_path('../../../work/grf-extract/kro-20211105/data/data')),
    'item_icon_relative_root' => 'texture/유저인터페이스/item',
    'item_illustration_relative_root' => 'texture/유저인터페이스/collection',
]];
