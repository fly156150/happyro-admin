<?php

namespace App\Services\GameServer;

use App\Data\GameServer\GameServerSettingDefinition;
use InvalidArgumentException;

final class GameServerSettingRegistry
{
    /** @return array<string, GameServerSettingDefinition> */
    public function definitions(): array
    {
        return [
            'base_exp_rate' => new GameServerSettingDefinition('base_exp_rate', 0, 2147483647, 'conf/battle/exp.conf'),
            'job_exp_rate' => new GameServerSettingDefinition('job_exp_rate', 0, 2147483647, 'conf/battle/exp.conf'),
            'item_rate_common' => new GameServerSettingDefinition('item_rate_common', 0, 1000000, 'conf/battle/drops.conf'),
            'item_rate_common_boss' => new GameServerSettingDefinition('item_rate_common_boss', 0, 1000000, 'conf/battle/drops.conf'),
            'item_rate_common_mvp' => new GameServerSettingDefinition('item_rate_common_mvp', 0, 1000000, 'conf/battle/drops.conf'),
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
}
