<?php

namespace Tests\Feature\AdventureTools;

use App\Contracts\GameData\ItemAssetRepository;
use App\Contracts\GameData\ItemRepository;
use App\Contracts\GameServer\GameServerGateway;
use App\Data\GameData\ItemQuery;
use App\Data\GameServer\GameServerCommandResult;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

final class AdventureItemControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.game' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
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
        DB::connection('game')->table('login')->insert(['account_id' => 2000001, 'group_id' => 0, 'web_auth_token' => 'token', 'web_auth_token_enabled' => 1]);
        DB::connection('game')->table('char')->insert(['char_id' => 150002, 'account_id' => 2000001, 'online' => 1]);
        $this->mock(ItemAssetRepository::class)->shouldReceive('iconPath', 'illustrationPath')->andReturnNull();
    }

    public function test_authenticated_player_can_search_client_supported_items(): void
    {
        $items = Mockery::mock(ItemRepository::class);
        $items->expects('search')->with(Mockery::on(fn (ItemQuery $query): bool => $query->range === 'client' && $query->query === '红药'))
            ->andReturn(['data' => [$this->item()], 'total' => 1]);
        $this->app->instance(ItemRepository::class, $items);

        $this->withHeaders($this->headers())->getJson('/api/adventure-tools/items?query=红药')
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.names.zh-CN', '红色药水');
    }

    public function test_everyone_policy_grants_only_to_authenticated_character_inventory(): void
    {
        $items = Mockery::mock(ItemRepository::class);
        $items->expects('find')->twice()->with(501, 'server')->andReturn($this->item());
        $this->app->instance(ItemRepository::class, $items);
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('battleConfig')->twice()->andReturn(['game_tools_item_grant_policy' => 2]);
        $gateway->expects('execute')->once()->andReturn(new GameServerCommandResult(['char_id' => 150002, 'item_id' => 501, 'amount' => 3]));
        $this->app->instance(GameServerGateway::class, $gateway);

        $this->withHeaders($this->headers())->postJson('/api/adventure-tools/items/grants', [
            'idempotency_key' => 'item-grant-1', 'target' => ['type' => 'self'], 'item_id' => 501, 'amount' => 3,
        ])->assertOk()->assertJsonPath('data.item_id', 501)->assertJsonPath('data.amount', 3);
        $this->withHeaders($this->headers())->postJson('/api/adventure-tools/items/grants', [
            'idempotency_key' => 'item-grant-1', 'target' => ['type' => 'self'], 'item_id' => 501, 'amount' => 3,
        ])->assertOk()->assertJsonPath('data.item_id', 501)->assertJsonPath('data.amount', 3);

        $this->assertDatabaseHas('game_server_commands', [
            'type' => 'character.inventory.item_grant', 'target_id' => '150002', 'requested_game_account_id' => 2000001,
        ]);
        $this->assertDatabaseCount('game_server_commands', 1);
    }

    public function test_rejects_other_targets(): void
    {
        $this->withHeaders($this->headers())->postJson('/api/adventure-tools/items/grants', [
            'idempotency_key' => 'item-grant-2', 'target' => ['type' => 'character'], 'item_id' => 501, 'amount' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('target.type');
    }

    public function test_admin_only_policy_rejects_a_regular_player(): void
    {
        $gateway = Mockery::mock(GameServerGateway::class);
        $gateway->expects('battleConfig')->once()->andReturn(['game_tools_item_grant_policy' => 1]);
        $this->app->instance(GameServerGateway::class, $gateway);
        $this->withHeaders($this->headers())->postJson('/api/adventure-tools/items/grants', [
            'idempotency_key' => 'item-grant-3', 'target' => ['type' => 'self'], 'item_id' => 501, 'amount' => 1,
        ])->assertForbidden();
    }

    private function headers(): array
    {
        return ['X-HappyRO-Account-ID' => '2000001', 'X-HappyRO-Character-ID' => '150002', 'X-HappyRO-Auth-Token' => 'token'];
    }

    private function item(): array
    {
        return ['Id' => 501, 'AegisName' => 'Red_Potion', 'Type' => 'Healing', 'names' => ['zh-CN' => '红色药水'], 'description' => ['恢复 HP']];
    }
}
