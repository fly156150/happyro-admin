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
}
