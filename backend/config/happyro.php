<?php

return ['players' => [
    'account_table' => env('GAME_ACCOUNT_TABLE', 'login'),
    'character_table' => env('GAME_CHARACTER_TABLE', 'char'),
    'login_log_table' => env('GAME_LOGIN_LOG_TABLE', 'loginlog'),
    'password_hash' => env('GAME_PASSWORD_HASH', false),
], 'game_data' => [
    'item_snapshots' => [
        'client' => base_path('resources/game-data/items/client-kro-20211105.json'),
        'renewal' => base_path('resources/game-data/items/renewal.json'),
    ],
    'default_client_version' => 'kro-20211105',
    'default_server_version' => '2fe6ab3dc4d8',
    'item_asset_map' => base_path('resources/game-data/items/item-assets.json'),
    'monster_snapshot' => base_path('resources/game-data/monsters/renewal.json'),
    'monster_image_root' => env('GAME_MONSTER_IMAGE_ROOT', base_path('../../../work/game-data/monsters/kro-20211105')),
    'map_index_path' => env('GAME_MAP_INDEX_PATH', base_path('../../happyro-server/db/map_index.txt')),
    'npc_root' => env('GAME_NPC_ROOT', base_path('../../happyro-server/npc')),
    'grf_root' => env('GAME_RESOURCE_ROOT', base_path('../../../work/grf-extract/kro-20211105/data/data')),
], 'game_control' => [
    'base_url' => env('GAME_CONTROL_BASE_URL', 'http://127.0.0.1:8889'),
    'token' => env('GAME_CONTROL_TOKEN', ''),
    'connect_timeout' => (int) env('GAME_CONTROL_CONNECT_TIMEOUT', 1),
    'timeout' => (int) env('GAME_CONTROL_TIMEOUT', 3),
    'battle_config_path' => env('GAME_SERVER_BATTLE_CONFIG_PATH', base_path('../../happyro-server/conf/import/battle_conf.txt')),
]];
