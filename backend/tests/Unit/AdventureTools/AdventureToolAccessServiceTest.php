<?php

namespace Tests\Unit\AdventureTools;

use App\Contracts\GameServer\GameServerGateway;
use App\Data\Auth\GameSessionPrincipal;
use App\Services\AdventureTools\AdventureToolAccessService;
use Mockery;
use Tests\TestCase;

final class AdventureToolAccessServiceTest extends TestCase
{
    public function test_missing_policy_defaults_to_admin_only(): void
    {
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('battleConfig')->twice()->andReturn([]);
        $service = new AdventureToolAccessService($gateway);
        $player = new GameSessionPrincipal(2000001, 150002, 0, false);
        $admin = new GameSessionPrincipal(2000001, 150002, 99, true);

        $this->assertFalse($service->allows('game_tools_item_grant_policy', $player));
        $this->assertTrue($service->allows('game_tools_item_grant_policy', $admin));
    }
}
