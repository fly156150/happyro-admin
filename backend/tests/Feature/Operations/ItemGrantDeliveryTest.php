<?php

namespace Tests\Feature\Operations;

use App\Contracts\GameData\ItemRepository;
use App\Contracts\GameServer\GameServerGateway;
use App\Data\GameServer\GameServerCommand;
use App\Data\GameServer\GameServerCommandResult;
use App\Exceptions\GameServerGatewayException;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery;
use Tests\TestCase;

final class ItemGrantDeliveryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_inventory_delivery_identifies_equipment_for_an_online_character(): void
    {
        $items = Mockery::mock(ItemRepository::class);
        $items->expects('find')->with(1101, 'server')->andReturn(['Id' => 1101, 'Type' => 'Weapon']);
        $this->app->instance(ItemRepository::class, $items);
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('execute')
            ->with(Mockery::on(fn (GameServerCommand $command): bool => $command->payload === [
                'item_id' => 1101,
                'amount' => 1,
                'identify' => true,
            ]))
            ->andReturn(new GameServerCommandResult(['char_id' => 150002, 'item_id' => 1101, 'amount' => 1]));
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->actingAs($this->superAdmin())->postJson('/api/operations/item-grants/mail', [
            'delivery' => 'inventory',
            'item_id' => 1101,
            'char_id' => 150002,
            'amount' => 1,
            'idempotency_key' => 'inventory-grant-1',
        ])->assertCreated()->assertJsonPath('data.delivery', 'inventory')->assertJsonPath('data.result.item_id', 1101);
    }

    public function test_mail_remains_the_default_delivery_method(): void
    {
        $this->actingAs($this->superAdmin())->postJson('/api/operations/item-grants/mail', [
            'item_id' => 1101,
            'char_id' => 150002,
            'amount' => 1,
            'idempotency_key' => 'mail-grant-1',
        ])->assertUnprocessable()->assertJsonValidationErrors(['title', 'message']);
    }

    public function test_inventory_delivery_retries_after_character_offline_409(): void
    {
        $items = Mockery::mock(ItemRepository::class);
        $items->expects('find')->twice()->with(501, 'server')->andReturn(['Id' => 501, 'Type' => 'Healing']);
        $this->app->instance(ItemRepository::class, $items);
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('execute')->twice()->andReturnUsing(function () {
            static $attempts = 0;
            $attempts++;
            if ($attempts === 1) {
                throw new GameServerGatewayException('character_offline', 'Game server rejected the request.');
            }

            return new GameServerCommandResult(['char_id' => 150002, 'item_id' => 501, 'amount' => 1]);
        });
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->actingAs($this->superAdmin())->postJson('/api/operations/item-grants/mail', [
            'delivery' => 'inventory',
            'item_id' => 501,
            'char_id' => 150002,
            'amount' => 1,
            'idempotency_key' => 'inventory-retry-1',
        ])->assertConflict()
            ->assertJsonPath('error.code', 'character_offline')
            ->assertJsonPath('error.message', '角色不在线。');
        $this->postJson('/api/operations/item-grants/mail', [
            'delivery' => 'inventory',
            'item_id' => 501,
            'char_id' => 150002,
            'amount' => 1,
            'idempotency_key' => 'inventory-retry-1',
        ])->assertCreated()->assertJsonPath('data.result.item_id', 501);

        $this->assertDatabaseHas('game_server_commands', [
            'idempotency_key' => 'inventory-retry-1',
            'status' => 'succeeded',
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
