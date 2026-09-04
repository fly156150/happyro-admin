<?php

namespace Tests\Feature\Settings;

use App\Contracts\GameServer\GameServerConfigWriter;
use App\Contracts\GameServer\GameServerGateway;
use App\Data\GameServer\GameServerCommandResult;
use App\Exceptions\GameServerGatewayException;
use App\Models\GameServerSettingRevision;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

final class GameServerSettingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_read_registered_values(): void
    {
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('battleConfig')->once()->andReturn(['base_exp_rate' => 100]);
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->actingAs($this->superAdmin())
            ->getJson('/api/settings/game-rules')
            ->assertOk()
            ->assertJsonPath('data.values.base_exp_rate', 100)
            ->assertJsonPath('data.definitions.base_exp_rate.source', 'conf/battle/exp.conf');
    }

    public function test_invalid_changes_are_rejected_before_service_execution(): void
    {
        $this->actingAs($this->superAdmin())
            ->putJson('/api/settings/game-rules', [
                'changes' => ['unknown_rate' => 100],
                'reason' => 'test',
            ])
            ->assertUnprocessable();
    }

    public function test_game_rules_return_service_unavailable_when_map_server_is_unreachable(): void
    {
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('battleConfig')->once()->andThrow(new GameServerGatewayException('map_server_unavailable', 'Map server is unavailable.'));
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->actingAs($this->superAdmin())->getJson('/api/settings/game-rules')
            ->assertServiceUnavailable()->assertJsonPath('message', '游戏服务暂时不可用');
    }

    public function test_experience_rate_uses_its_registered_upper_bound(): void
    {
        $writer = Mockery::mock(GameServerConfigWriter::class);
        $writer->expects('snapshot')->once()->andReturn('old config');
        $writer->expects('write')->once()->with(['base_exp_rate' => 1000001]);
        $this->app->instance(GameServerConfigWriter::class, $writer);

        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('execute')->once()->andReturn(new GameServerCommandResult([
            'changes' => [['key' => 'base_exp_rate', 'value' => 1000001]],
        ]));
        $gateway->expects('battleConfig')->once()->andReturn(['base_exp_rate' => 1000001]);
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->actingAs($this->superAdmin())
            ->putJson('/api/settings/game-rules', [
                'changes' => ['base_exp_rate' => 1000001],
                'reason' => 'verify registered limit',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'applied');
    }

    public function test_game_rules_require_settings_permission(): void
    {
        $role = Role::query()->create(['name' => 'viewer', 'label' => '查看者']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->actingAs($user)->getJson('/api/settings/game-rules')->assertForbidden();
    }

    public function test_super_admin_can_query_game_rule_history(): void
    {
        $user = $this->superAdmin();
        GameServerSettingRevision::query()->create(['server_key' => 'primary', 'revision' => 1, 'changes' => ['base_exp_rate' => 200], 'status' => 'applied', 'reason' => 'event', 'requested_by' => $user->id, 'applied_at' => now()]);
        $this->actingAs($user)->getJson('/api/settings/game-rules/history')->assertOk()->assertJsonPath('data.0.revision', 1)->assertJsonPath('data.0.changes.base_exp_rate', 200)->assertJsonPath('data.0.requester.id', $user->id)->assertJsonPath('meta.total', 1);
    }

    private function superAdmin(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'super_admin'], ['label' => '超级管理员']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
