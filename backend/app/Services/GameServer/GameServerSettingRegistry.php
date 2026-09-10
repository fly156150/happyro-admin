<?php

namespace App\Services\GameServer;

use App\Data\GameServer\GameServerSettingDefinition;
use InvalidArgumentException;

final class GameServerSettingRegistry
{
    private const ADVENTURE_TOOL_GOVERNANCE_KEYS = [
        'game_tools_character_maintenance_policy',
        'game_tools_game_settings_policy',
        'game_tools_item_grant_policy',
    ];

    /** @return array<string, GameServerSettingDefinition> */
    public function definitions(): array
    {
        return [
            'base_exp_rate' => new GameServerSettingDefinition('base_exp_rate', 0, 2147483647, 'conf/battle/exp.conf'),
            'job_exp_rate' => new GameServerSettingDefinition('job_exp_rate', 0, 2147483647, 'conf/battle/exp.conf'),
            'item_rate_common' => new GameServerSettingDefinition('item_rate_common', 0, 1000000, 'conf/battle/drops.conf'),
            'item_rate_common_boss' => new GameServerSettingDefinition('item_rate_common_boss', 0, 1000000, 'conf/battle/drops.conf'),
            'item_rate_common_mvp' => new GameServerSettingDefinition('item_rate_common_mvp', 0, 1000000, 'conf/battle/drops.conf'),
            'item_rate_heal' => new GameServerSettingDefinition('item_rate_heal', 0, 1000000, 'conf/battle/drops.conf'),
            'item_rate_heal_mvp' => new GameServerSettingDefinition('item_rate_heal_mvp', 0, 1000000, 'conf/battle/drops.conf'),
            'item_rate_use' => new GameServerSettingDefinition('item_rate_use', 0, 1000000, 'conf/battle/drops.conf'),
            'item_rate_use_mvp' => new GameServerSettingDefinition('item_rate_use_mvp', 0, 1000000, 'conf/battle/drops.conf'),
            'item_rate_equip' => new GameServerSettingDefinition('item_rate_equip', 0, 1000000, 'conf/battle/drops.conf'),
            'item_rate_equip_mvp' => new GameServerSettingDefinition('item_rate_equip_mvp', 0, 1000000, 'conf/battle/drops.conf'),
            'item_rate_card' => new GameServerSettingDefinition('item_rate_card', 0, 1000000, 'conf/battle/drops.conf'),
            'item_rate_card_boss' => new GameServerSettingDefinition('item_rate_card_boss', 0, 1000000, 'conf/battle/drops.conf'),
            'item_rate_card_mvp' => new GameServerSettingDefinition('item_rate_card_mvp', 0, 1000000, 'conf/battle/drops.conf'),
            'navigation_teleport_policy' => new GameServerSettingDefinition('navigation_teleport_policy', 0, 2, 'conf/import/battle_conf.txt', 'policy'),
            'navigation_teleport_cross_map' => new GameServerSettingDefinition('navigation_teleport_cross_map', 0, 1, 'conf/import/battle_conf.txt', 'boolean'),
            'navigation_teleport_cooldown' => new GameServerSettingDefinition('navigation_teleport_cooldown', 0, 3600, 'conf/import/battle_conf.txt', 'seconds'),
            'navigation_map_channels_enabled' => new GameServerSettingDefinition('navigation_map_channels_enabled', 0, 1, 'conf/import/battle_conf.txt', 'boolean'),
            'game_tools_monster_spawn_policy' => new GameServerSettingDefinition('game_tools_monster_spawn_policy', 0, 2, 'conf/import/battle_conf.txt', 'policy'),
            'game_tools_monster_spawn_cooldown' => new GameServerSettingDefinition('game_tools_monster_spawn_cooldown', 0, 3600, 'conf/import/battle_conf.txt', 'seconds'),
            'game_tools_monster_spawn_duration' => new GameServerSettingDefinition('game_tools_monster_spawn_duration', 1, 3600, 'conf/import/battle_conf.txt', 'seconds'),
            'game_tools_monster_spawn_allow_boss' => new GameServerSettingDefinition('game_tools_monster_spawn_allow_boss', 0, 1, 'conf/import/battle_conf.txt', 'boolean'),
            'game_tools_character_maintenance_policy' => new GameServerSettingDefinition('game_tools_character_maintenance_policy', 1, 2, 'conf/import/battle_conf.txt', 'policy'),
            'game_tools_game_settings_policy' => new GameServerSettingDefinition('game_tools_game_settings_policy', 1, 2, 'conf/import/battle_conf.txt', 'policy'),
            'game_tools_item_grant_policy' => new GameServerSettingDefinition('game_tools_item_grant_policy', 1, 2, 'conf/import/battle_conf.txt', 'policy'),
        ];
    }

    public function validate(string $key, int $value): GameServerSettingDefinition
    {
        $definition = $this->definitions()[$key] ?? throw new InvalidArgumentException('Unknown game server setting.');
        if ($value < $definition->minimum || $value > $definition->maximum) {
            throw new InvalidArgumentException('Game server setting value is outside the allowed range.');
        }

        return $definition;
    }

    /** @return array<string, GameServerSettingDefinition> */
    public function adventureToolDefinitions(): array
    {
        return array_diff_key(
            $this->definitions(),
            array_fill_keys(self::ADVENTURE_TOOL_GOVERNANCE_KEYS, true),
        );
    }
}
