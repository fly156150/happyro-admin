<?php

namespace Tests\Feature\GameServer;

use App\Contracts\GameServer\GameServerGateway;
use App\Data\GameServer\GameServerCommandResult;
use App\Exceptions\GameServerGatewayException;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery;
use Tests\TestCase;

final class GameServerCommandControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authenticated_operator_can_submit_a_command(): void
    {
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('execute')
            ->once()
            ->andReturn(new GameServerCommandResult(['char_id' => 42]));
        $this->app->instance(GameServerGateway::class, $gateway);

        $response = $this->actingAs($this->superAdmin())->postJson('/api/operations/game-control/commands', [
            'idempotency_key' => 'character-update-1',
            'type' => 'character.progression.update',
            'target' => ['type' => 'character', 'id' => '42'],
            'payload' => ['base_level' => 99],
        ]);

        $response->assertStatus(202)->assertJsonPath('data.result.char_id', 42);
        $this->assertDatabaseHas('game_server_commands', [
            'idempotency_key' => 'character-update-1',
            'status' => 'succeeded',
        ]);
    }

    public function test_command_can_require_an_explicit_empty_payload(): void
    {
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('execute')
            ->once()
            ->andReturn(new GameServerCommandResult(['char_id' => 42]));
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->actingAs($this->superAdmin())->postJson('/api/operations/game-control/commands', [
            'idempotency_key' => 'character-vitals-1',
            'type' => 'character.vitals.restore',
            'target' => ['type' => 'character', 'id' => '42'],
            'payload' => [],
        ])->assertStatus(202);
    }

    public function test_authenticated_operator_can_set_skill_points(): void
    {
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('execute')
            ->once()
            ->andReturn(new GameServerCommandResult(['char_id' => 42, 'skill_points' => 1000]));
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->actingAs($this->superAdmin())->postJson('/api/operations/game-control/commands', [
            'idempotency_key' => 'character-skill-points-1',
            'type' => 'character.skill_points.update',
            'target' => ['type' => 'character', 'id' => '42'],
            'payload' => ['skill_points' => 1000],
        ])->assertStatus(202)->assertJsonPath('data.result.skill_points', 1000);

        $this->assertDatabaseHas('game_server_commands', [
            'idempotency_key' => 'character-skill-points-1',
            'type' => 'character.skill_points.update',
            'status' => 'succeeded',
        ]);
    }

    public function test_command_payload_must_be_present(): void
    {
        $this->actingAs($this->superAdmin())->postJson('/api/operations/game-control/commands', [
            'idempotency_key' => 'character-vitals-2',
            'type' => 'character.vitals.restore',
            'target' => ['type' => 'character', 'id' => '42'],
        ])->assertUnprocessable()->assertJsonValidationErrors('payload');
    }

    public function test_battle_config_command_is_rejected_by_operations_endpoint(): void
    {
        $this->actingAs($this->superAdmin())->postJson('/api/operations/game-control/commands', [
            'idempotency_key' => 'battle-config-1',
            'type' => 'battle_config.apply',
            'target' => ['type' => 'server', 'id' => 'primary'],
            'payload' => ['changes' => [['key' => 'base_exp_rate', 'value' => 200]]],
        ])->assertUnprocessable()->assertJsonValidationErrors(['type', 'target.type']);

        $this->assertDatabaseMissing('game_server_commands', [
            'idempotency_key' => 'battle-config-1',
        ]);
    }

    public function test_operator_can_learn_job_skills_without_spending_points(): void
    {
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('execute')->once()
            ->withArgs(fn ($command): bool => $command->type->value === 'character.skills.learn_all' && $command->payload === [])
            ->andReturn(new GameServerCommandResult(['char_id' => 42, 'learned_skills' => 85, 'skill_points' => 3]));
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->actingAs($this->superAdmin())->postJson('/api/operations/game-control/commands', [
            'idempotency_key' => 'learn-job-skills',
            'type' => 'character.skills.learn_all',
            'target' => ['type' => 'character', 'id' => '42'],
            'payload' => [],
        ])->assertStatus(202)->assertJsonPath('data.result.skill_points', 3);
    }

    public function test_operator_maintenance_rejects_invalid_and_unrelated_fields(): void
    {
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->shouldNotReceive('execute');
        $this->app->instance(GameServerGateway::class, $gateway);
        $this->actingAs($this->superAdmin());

        foreach ([
            ['character.traits.update', ['pow' => -1]],
            ['character.stats.update', ['pow' => 10]],
            ['character.progression.update', []],
            ['character.skills.learn_all', ['skill_points' => 100]],
        ] as $index => [$type, $payload]) {
            $this->postJson('/api/operations/game-control/commands', [
                'idempotency_key' => 'invalid-maintenance-'.$index,
                'type' => $type,
                'target' => ['type' => 'character', 'id' => '42'],
                'payload' => $payload,
            ])->assertUnprocessable();
        }
        $this->assertDatabaseCount('game_server_commands', 0);
    }

    public function test_operator_reads_live_character_limits_and_traits(): void
    {
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('characterSnapshot')->with(42)->once()->andReturn([
            'char_id' => 42, 'base_level' => 210, 'max_base_level' => 250,
            'traits' => ['enabled' => true, 'values' => ['pow' => 12]],
        ]);
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->actingAs($this->superAdmin())->getJson('/api/operations/game-control/characters/42')
            ->assertOk()->assertJsonPath('data.max_base_level', 250)->assertJsonPath('data.traits.values.pow', 12);
    }

    public function test_character_snapshot_requires_operations_permission(): void
    {
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->shouldNotReceive('characterSnapshot');
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->actingAs(User::factory()->create())->getJson('/api/operations/game-control/characters/42')
            ->assertForbidden();
    }

    public function test_operator_can_teleport_to_a_live_npc_using_the_shared_gateway(): void
    {
        $payload = ['map' => 'prontera', 'x' => 150, 'y' => 180, 'npc_class' => 83];
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('execute')->once()->withArgs(fn ($command): bool => $command->type->value === 'character.navigation.teleport' && $command->payload === $payload)
            ->andReturn(new GameServerCommandResult(['char_id' => 42, 'map' => 'prontera', 'x' => 149, 'y' => 179]));
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->actingAs($this->superAdmin())->postJson('/api/operations/game-control/commands', [
            'idempotency_key' => 'npc-teleport',
            'type' => 'character.navigation.teleport',
            'target' => ['type' => 'character', 'id' => '42'],
            'payload' => $payload,
        ])->assertStatus(202)->assertJsonPath('data.result.map', 'prontera');
    }

    public function test_navigation_cooldown_is_a_conflict_not_a_gateway_failure(): void
    {
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('execute')->once()->andThrow(new GameServerGatewayException('navigation_cooldown', 'Cooldown'));
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->actingAs($this->superAdmin())->postJson('/api/operations/game-control/commands', [
            'idempotency_key' => 'teleport-cooldown',
            'type' => 'character.navigation.teleport',
            'target' => ['type' => 'character', 'id' => '42'],
            'payload' => ['map' => 'prontera', 'x' => 0, 'y' => 0],
        ])->assertConflict()->assertJsonPath('error.code', 'navigation_cooldown');
    }

    public function test_navigation_rejects_invalid_coordinates_before_execution(): void
    {
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->shouldNotReceive('execute');
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->actingAs($this->superAdmin())->postJson('/api/operations/game-control/commands', [
            'idempotency_key' => 'teleport-invalid',
            'type' => 'character.navigation.teleport',
            'target' => ['type' => 'character', 'id' => '42'],
            'payload' => ['map' => 'prontera', 'x' => -1, 'y' => 0],
        ])->assertUnprocessable()->assertJsonValidationErrors('payload.x');
    }

    public function test_operator_can_stop_client_auto_walk_without_coordinates(): void
    {
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('execute')->once()->withArgs(fn ($command): bool => $command->type->value === 'character.navigation.route' && $command->payload === ['action' => 'stop'])
            ->andReturn(new GameServerCommandResult(['char_id' => 42, 'dispatched' => true]));
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->actingAs($this->superAdmin())->postJson('/api/operations/game-control/commands', [
            'idempotency_key' => 'route-stop',
            'type' => 'character.navigation.route',
            'target' => ['type' => 'character', 'id' => '42'],
            'payload' => ['action' => 'stop'],
        ])->assertStatus(202)->assertJsonPath('data.result.dispatched', true);
    }

    private function superAdmin(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'super_admin'], ['label' => '超级管理员']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
