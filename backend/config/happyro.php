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
    'item_image_root' => env('GAME_ITEM_IMAGE_ROOT', base_path('../../../work/game-data/items/kro-20211105')),
    'monster_snapshot' => base_path('resources/game-data/monsters/renewal.json'),
    'monster_image_root' => env('GAME_MONSTER_IMAGE_ROOT', base_path('../../../work/game-data/monsters/kro-20211105')),
    'map_index_path' => env('GAME_MAP_INDEX_PATH', base_path('../../happyro-server/db/map_index.txt')),
    'npc_catalog_path' => env('GAME_NPC_CATALOG_PATH', base_path('resources/game-data/world/npc-catalog.json')),
    'world_asset_version' => 'kro-20211105-transparent-v2',
], 'game_control' => [
    'base_url' => env('GAME_CONTROL_BASE_URL', 'http://127.0.0.1:8889'),
    'token' => env('GAME_CONTROL_TOKEN', ''),
    'connect_timeout' => (int) env('GAME_CONTROL_CONNECT_TIMEOUT', 1),
    'timeout' => (int) env('GAME_CONTROL_TIMEOUT', 3),
    'battle_config_path' => env('GAME_SERVER_BATTLE_CONFIG_PATH', base_path('../../happyro-server/conf/import/battle_conf.txt')),
],
    'adventure_tools' => [
        'admin_group_ids' => array_values(array_filter(array_map(
            static fn (string $value): int => (int) trim($value),
            explode(',', env('GAME_ADMIN_GROUP_IDS', '2,3,4,10,99')),
        ), static fn (int $value): bool => $value > 0)),
    ]];
