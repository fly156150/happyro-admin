<?php

namespace Tests\Unit\GameServer;

use App\Services\GameServer\GameServerSettingRegistry;
use InvalidArgumentException;
use Tests\TestCase;

final class GameServerSettingRegistryTest extends TestCase
{
    public function test_validates_registered_setting(): void
    {
        $definition = (new GameServerSettingRegistry)->validate('base_exp_rate', 100);

        $this->assertSame('conf/battle/exp.conf', $definition->source);
    }

    public function test_rejects_unknown_setting(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new GameServerSettingRegistry)->validate('arbitrary_config', 100);
    }

    public function test_registers_navigation_teleport_settings_with_strict_ranges(): void
    {
        $registry = new GameServerSettingRegistry;

        $this->assertSame('policy', $registry->validate('navigation_teleport_policy', 2)->unit);
        $this->assertSame('boolean', $registry->validate('navigation_teleport_cross_map', 1)->unit);
        $this->assertSame('seconds', $registry->validate('navigation_teleport_cooldown', 3600)->unit);
        $this->assertSame('boolean', $registry->validate('navigation_map_channels_enabled', 0)->unit);

        $this->expectException(InvalidArgumentException::class);
        $registry->validate('navigation_teleport_policy', 3);
    }

    public function test_registers_game_tools_monster_spawn_settings_with_strict_ranges(): void
    {
        $registry = new GameServerSettingRegistry;

        $this->assertSame('policy', $registry->validate('game_tools_monster_spawn_policy', 1)->unit);
        $this->assertSame('seconds', $registry->validate('game_tools_monster_spawn_cooldown', 3600)->unit);
        $this->assertSame('seconds', $registry->validate('game_tools_monster_spawn_duration', 60)->unit);
        $this->assertSame('boolean', $registry->validate('game_tools_monster_spawn_allow_boss', 0)->unit);

        $this->expectException(InvalidArgumentException::class);
        $registry->validate('game_tools_monster_spawn_duration', 0);
    }

    public function test_registers_item_grant_policy_as_everyone_or_admin(): void
    {
        $registry = new GameServerSettingRegistry;

        $this->assertSame('policy', $registry->validate('game_tools_item_grant_policy', 2)->unit);

        $this->expectException(InvalidArgumentException::class);
        $registry->validate('game_tools_item_grant_policy', 0);
    }
}
