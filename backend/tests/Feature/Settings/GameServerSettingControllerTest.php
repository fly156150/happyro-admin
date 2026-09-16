<?php

namespace Tests\Feature\Settings;

use App\Contracts\GameServer\GameServerConfigWriter;
use App\Contracts\GameServer\GameServerGateway;
use App\Data\GameServer\GameServerCommandResult;
use App\Exceptions\GameServerGatewayException;
use App\Models\GameServerSettingRevision;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery;
use Tests\TestCase;

final class GameServerSettingControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_applies_independent_normal_mini_and_mvp_drop_rates(): void
    {
        $this->actingAs($this->superAdmin());
        $changes = [
            'item_rate_card' => 100,
            'item_rate_card_boss' => 1000000,
            'item_rate_card_mvp' => 300,
            'item_rate_common_boss' => 400,
            'item_rate_heal_boss' => 500,
            'item_rate_use_boss' => 600,
            'item_rate_equip_boss' => 700,
        ];
        $writer = Mockery::mock(GameServerConfigWriter::class);
        $writer->expects('snapshot')->once()->andReturn('old config');
        $writer->expects('write')->once()->with($changes);
        $this->app->instance(GameServerConfigWriter::class, $writer);
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('execute')->once()->with(Mockery::on(
            fn ($command) => array_column($command->payload['changes'], 'value', 'key') == $changes,
        ))->andReturn(new GameServerCommandResult(['changes' => []]));
        $gateway->expects('battleConfig')->twice()->andReturn($changes + ['game_tools_game_settings_policy' => 2]);
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->putJson('/api/settings/game-settings', ['changes' => $changes])
            ->assertOk()->assertJsonPath('data.status', 'applied');

        foreach ($changes as $key => $value) {
            $this->assertDatabaseHas('game_server_settings', ['setting_key' => $key, 'actual_value' => $value]);
        }
        $this->getJson('/api/settings/game-settings')->assertOk()
            ->assertJsonPath('data.values.item_rate_card_boss', 1000000)
            ->assertJsonPath('data.definitions.item_rate_heal_boss.maximum', 1000000)
            ->assertJsonPath('data.definitions.item_rate_use_boss.unit', 'percent')
            ->assertJsonPath('data.definitions.item_rate_equip_boss.minimum', 0);
    }

    public function test_super_admin_can_read_registered_values(): void
    {
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('battleConfig')->once()->andReturn(['base_exp_rate' => 100]);
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->actingAs($this->superAdmin())
            ->getJson('/api/settings/game-settings')
            ->assertOk()
            ->assertJsonPath('data.values.base_exp_rate', 100)
            ->assertJsonPath('data.definitions.base_exp_rate.source', 'conf/battle/exp.conf')
            ->assertJsonPath('data.definitions.navigation_teleport_policy.unit', 'policy')
            ->assertJsonPath('data.definitions.navigation_teleport_policy.maximum', 2);
    }

    public function test_invalid_changes_are_rejected_before_service_execution(): void
    {
        $this->actingAs($this->superAdmin())
            ->putJson('/api/settings/game-settings', [
                'changes' => ['unknown_rate' => 100],
            ])
            ->assertUnprocessable();
    }

    public function test_game_settings_return_service_unavailable_when_map_server_is_unreachable(): void
    {
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('battleConfig')->once()->andThrow(new GameServerGatewayException('map_server_unavailable', 'Map server is unavailable.'));
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->actingAs($this->superAdmin())->getJson('/api/settings/game-settings')
            ->assertServiceUnavailable()->assertJsonPath('message', '游戏服务暂时不可用');
    }

    public function test_navigation_teleport_policy_rejects_values_outside_registered_range(): void
    {
        $this->actingAs($this->superAdmin())
            ->putJson('/api/settings/game-settings', [
                'changes' => ['navigation_teleport_policy' => 3],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('changes.navigation_teleport_policy');
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
            ->putJson('/api/settings/game-settings', [
                'changes' => ['base_exp_rate' => 1000001],
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'applied');
    }

    public function test_game_settings_require_settings_permission(): void
    {
        $role = Role::query()->create(['name' => 'viewer', 'label' => '查看者']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->actingAs($user)->getJson('/api/settings/game-settings')->assertForbidden();
    }

    public function test_super_admin_can_query_game_setting_history(): void
    {
        $user = $this->superAdmin();
        GameServerSettingRevision::query()->create(['server_key' => 'primary', 'revision' => 1, 'changes' => ['base_exp_rate' => 200], 'status' => 'applied', 'requested_by' => $user->id, 'applied_at' => now()]);
        $this->actingAs($user)->getJson('/api/settings/game-settings/history')->assertOk()->assertJsonPath('data.0.revision', 1)->assertJsonPath('data.0.changes.base_exp_rate', 200)->assertJsonPath('data.0.requester.id', $user->id)->assertJsonPath('meta.total', 1)->assertJsonMissingPath('data.0.remark');
    }

    private function superAdmin(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'super_admin'], ['label' => '超级管理员']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
