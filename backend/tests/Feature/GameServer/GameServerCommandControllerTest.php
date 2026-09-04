<?php

namespace Tests\Feature\GameServer;

use App\Contracts\GameServer\GameServerGateway;
use App\Data\GameServer\GameServerCommandResult;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

final class GameServerCommandControllerTest extends TestCase
{
    use RefreshDatabase;

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

    private function superAdmin(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'super_admin'], ['label' => '超级管理员']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
