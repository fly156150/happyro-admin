<?php

namespace Tests\Feature\AdventureTools;

use App\Contracts\GameServer\GameServerConfigWriter;
use App\Contracts\GameServer\GameServerGateway;
use App\Data\GameServer\GameServerCommandResult;
use App\Exceptions\GameServerGatewayException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

final class AdventureToolControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const TOKEN = 'game-session-token';

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.game' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
        DB::purge('game');
        Schema::connection('game')->create('login', function (Blueprint $table): void {
            $table->integer('account_id')->primary();
            $table->integer('group_id')->default(0);
            $table->string('web_auth_token')->nullable();
            $table->integer('web_auth_token_enabled')->default(0);
        });
        Schema::connection('game')->create('char', function (Blueprint $table): void {
            $table->integer('char_id')->primary();
            $table->integer('account_id');
            $table->integer('online')->default(0);
        });
    }

    public function test_applies_independent_normal_mini_and_mvp_drop_rates(): void
    {
        $this->createSession(groupId: 0);
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
        $gateway->expects('battleConfig')->times(4)->andReturn($changes + ['game_tools_game_settings_policy' => 2]);
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->withHeaders($this->headers())->putJson('/api/adventure-tools/game-settings', ['changes' => $changes])
            ->assertOk()->assertJsonPath('data.values.item_rate_card_boss', 1000000);

        foreach ($changes as $key => $value) {
            $this->assertDatabaseHas('game_server_settings', ['setting_key' => $key, 'actual_value' => $value]);
        }
        $this->withHeaders($this->headers())->getJson('/api/adventure-tools/game-settings')->assertOk()
            ->assertJsonPath('data.values.item_rate_card_boss', 1000000)
            ->assertJsonPath('data.definitions.item_rate_heal_boss.maximum', 1000000)
            ->assertJsonPath('data.definitions.item_rate_use_boss.unit', 'percent')
            ->assertJsonPath('data.definitions.item_rate_equip_boss.minimum', 0);
    }

    public function test_rejects_an_invalid_game_session(): void
    {
        $this->getJson('/api/adventure-tools/character')->assertUnauthorized();
    }

    public function test_everyone_policy_allows_maintenance_of_the_authenticated_character(): void
    {
        $this->createSession(groupId: 0);
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('battleConfig')->once()->andReturn(['game_tools_character_maintenance_policy' => 2]);
        $gateway->expects('execute')->once()->andReturn(new GameServerCommandResult(['char_id' => 150002]));
        $gateway->expects('characterSnapshot')->once()->with(150002)->andReturn($this->snapshot());
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->withHeaders($this->headers())->postJson('/api/adventure-tools/character/commands', [
            'idempotency_key' => 'maintenance-1',
            'type' => 'character.vitals.restore',
            'payload' => [],
        ])->assertOk()->assertJsonPath('data.str', 20);

        $this->assertDatabaseHas('game_server_commands', [
            'target_id' => '150002',
            'requested_by' => null,
            'requested_game_account_id' => 2000001,
        ]);
    }

    public function test_admin_policy_uses_the_configured_game_group(): void
    {
        $this->createSession(groupId: 0);
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('battleConfig')->twice()->andReturn(['game_tools_character_maintenance_policy' => 1]);
        $gateway->expects('characterSnapshot')->once()->with(150002)->andReturn($this->snapshot());
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->withHeaders($this->headers())->getJson('/api/adventure-tools/character')->assertForbidden();

        DB::connection('game')->table('login')->where('account_id', 2000001)->update(['group_id' => 99]);

        $this->withHeaders($this->headers())->getJson('/api/adventure-tools/character')->assertOk();
    }

    public function test_character_must_be_online_and_owned_by_the_account(): void
    {
        $this->createSession(groupId: 0, online: false);

        $this->withHeaders($this->headers())->getJson('/api/adventure-tools/character')->assertUnauthorized();

        DB::connection('game')->table('char')->where('char_id', 150002)->update([
            'account_id' => 2000002,
            'online' => 1,
        ]);

        $this->withHeaders($this->headers())->getJson('/api/adventure-tools/character')->assertUnauthorized();
    }

    public function test_game_settings_policy_defaults_to_everyone_and_can_require_admin(): void
    {
        $this->createSession(groupId: 0);
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('battleConfig')->twice()->andReturn(
            ['game_tools_game_settings_policy' => 2],
            ['game_tools_game_settings_policy' => 1],
        );
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->withHeaders($this->headers())->getJson('/api/adventure-tools/game-settings')
            ->assertOk()
            ->assertJsonMissingPath('data.values.game_tools_game_settings_policy')
            ->assertJsonMissingPath('data.definitions.game_tools_character_maintenance_policy');

        $this->withHeaders($this->headers())->getJson('/api/adventure-tools/game-settings')->assertForbidden();
    }

    public function test_rejects_governance_settings_from_the_game_client(): void
    {
        $this->createSession(groupId: 0);

        $this->withHeaders($this->headers())->putJson('/api/adventure-tools/game-settings', [
            'changes' => ['game_tools_game_settings_policy' => 1],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('changes');
    }

    public function test_accepts_skill_points_above_the_current_job_level(): void
    {
        $this->createSession(groupId: 0);
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('battleConfig')->once()->andReturn(['game_tools_character_maintenance_policy' => 2]);
        $gateway->expects('execute')->once()->andReturn(new GameServerCommandResult(['skill_points' => 1000]));
        $gateway->expects('characterSnapshot')->once()->with(150002)->andReturn([
            ...$this->snapshot(),
            'skill_points' => 1000,
        ]);
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->withHeaders($this->headers())->postJson('/api/adventure-tools/character/commands', [
            'idempotency_key' => 'skill-points-1',
            'type' => 'character.skill_points.update',
            'payload' => ['skill_points' => 1000],
        ])->assertOk()->assertJsonPath('data.skill_points', 1000);

        $this->assertDatabaseHas('game_server_commands', [
            'idempotency_key' => 'skill-points-1',
            'type' => 'character.skill_points.update',
            'status' => 'succeeded',
        ]);
    }

    public function test_updates_both_point_balances_in_one_command_for_the_authenticated_character(): void
    {
        $this->createSession(groupId: 0);
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('battleConfig')->once()->andReturn(['game_tools_character_maintenance_policy' => 2]);
        $gateway->expects('execute')->once()->with(Mockery::on(
            fn ($command) => $command->type->value === 'character.points.update'
                && $command->targetId === '150002'
                && $command->payload === ['skill_points' => 10, 'status_points' => 20],
        ))->andReturn(new GameServerCommandResult(['skill_points' => 10, 'status_points' => 20]));
        $gateway->expects('characterSnapshot')->once()->with(150002)->andReturn([
            ...$this->snapshot(), 'skill_points' => 10, 'status_points' => 20,
        ]);
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->withHeaders($this->headers())->postJson('/api/adventure-tools/character/commands', [
            'idempotency_key' => 'points-1', 'type' => 'character.points.update',
            'payload' => ['skill_points' => 10, 'status_points' => 20],
        ])->assertOk()->assertJsonPath('data.skill_points', 10)->assertJsonPath('data.status_points', 20);

        $this->assertDatabaseHas('game_server_commands', [
            'idempotency_key' => 'points-1', 'type' => 'character.points.update', 'status' => 'succeeded',
        ]);
    }

    public function test_rejects_invalid_point_balances_without_executing_a_command(): void
    {
        $this->createSession(groupId: 0);
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->shouldNotReceive('execute');
        $this->app->instance(GameServerGateway::class, $gateway);

        foreach ([[], ['status_points' => -1], ['status_points' => 2147483648], ['skill_points' => 32768], ['status_points' => 2, 'base_level' => 10]] as $payload) {
            $this->withHeaders($this->headers())->postJson('/api/adventure-tools/character/commands', [
                'idempotency_key' => 'points-invalid', 'type' => 'character.points.update', 'payload' => $payload,
            ])->assertUnprocessable();
        }

        $this->assertDatabaseMissing('game_server_commands', ['idempotency_key' => 'points-invalid']);
    }

    public function test_returns_service_unavailable_when_game_control_is_offline(): void
    {
        $this->createSession(groupId: 0);
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('battleConfig')->once()->andThrow(
            new GameServerGatewayException('unavailable', 'Game server is unavailable.'),
        );
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->withHeaders($this->headers())->getJson('/api/adventure-tools/character')
            ->assertServiceUnavailable()
            ->assertJsonPath('error.code', 'unavailable');
    }

    public function test_applies_traits_to_the_authenticated_character_and_returns_updated_points(): void
    {
        $this->createSession(groupId: 0);
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('battleConfig')->once()->andReturn(['game_tools_character_maintenance_policy' => 2]);
        $gateway->expects('execute')->once()->with(Mockery::on(
            fn ($command) => $command->type->value === 'character.traits.update'
                && $command->payload === ['pow' => 292],
        ))->andReturn(new GameServerCommandResult([]));
        $gateway->expects('characterSnapshot')->once()->with(150002)->andReturn([
            ...$this->snapshot(), 'traits' => ['values' => ['pow' => 292], 'points' => 7],
        ]);
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->withHeaders($this->headers())->postJson('/api/adventure-tools/character/commands', [
            'idempotency_key' => 'traits-1', 'type' => 'character.traits.update', 'payload' => ['pow' => 292],
        ])->assertOk()->assertJsonPath('data.traits.values.pow', 292)->assertJsonPath('data.traits.points', 7);

        $this->assertDatabaseHas('game_server_commands', [
            'idempotency_key' => 'traits-1', 'type' => 'character.traits.update',
            'target_id' => '150002', 'status' => 'succeeded',
        ]);
    }

    public function test_rejects_cross_operation_trait_parameters_with_422_without_execution(): void
    {
        $this->createSession(groupId: 0);
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->shouldNotReceive('execute');
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->withHeaders($this->headers())->postJson('/api/adventure-tools/character/commands', [
            'idempotency_key' => 'traits-invalid', 'type' => 'character.traits.update',
            'payload' => ['pow' => 1, 'job_id' => 4252],
        ])->assertUnprocessable()->assertJsonValidationErrors('payload')
            ->assertJsonPath('errors.payload.0', '操作参数无效');

        $this->assertDatabaseMissing('game_server_commands', ['idempotency_key' => 'traits-invalid']);
    }

    public function test_rejects_out_of_range_trait_values_with_422(): void
    {
        $this->createSession(groupId: 0);
        foreach ([-1, 32768] as $value) {
            $this->withHeaders($this->headers())->postJson('/api/adventure-tools/character/commands', [
                'idempotency_key' => 'traits-invalid-range', 'type' => 'character.traits.update', 'payload' => ['pow' => $value],
            ])->assertUnprocessable()->assertJsonValidationErrors('payload.pow');
        }
        $this->assertDatabaseMissing('game_server_commands', ['idempotency_key' => 'traits-invalid-range']);
    }

    public function test_forbids_trait_reset_under_admin_only_policy_with_403(): void
    {
        $this->createSession(groupId: 0);
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('battleConfig')->once()->andReturn(['game_tools_character_maintenance_policy' => 1]);
        $gateway->shouldNotReceive('execute');
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->withHeaders($this->headers())->postJson('/api/adventure-tools/character/commands', [
            'idempotency_key' => 'traits-forbidden', 'type' => 'character.traits.reset', 'payload' => [],
        ])->assertForbidden();

        $this->assertDatabaseMissing('game_server_commands', ['idempotency_key' => 'traits-forbidden']);
    }

    private function createSession(int $groupId, bool $online = true): void
    {
        DB::connection('game')->table('login')->insert([
            'account_id' => 2000001,
            'group_id' => $groupId,
            'web_auth_token' => self::TOKEN,
            'web_auth_token_enabled' => 1,
        ]);
        DB::connection('game')->table('char')->insert([
            'char_id' => 150002,
            'account_id' => 2000001,
            'online' => $online ? 1 : 0,
        ]);
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        return [
            'X-HappyRO-Account-ID' => '2000001',
            'X-HappyRO-Character-ID' => '150002',
            'X-HappyRO-Auth-Token' => self::TOKEN,
        ];
    }

    /** @return array<string, mixed> */
    private function snapshot(): array
    {
        return [
            'char_id' => 150002,
            'name' => '测试角色',
            'str' => 20,
        ];
    }
}
