<?php

namespace Tests\Feature\AdventureTools;

use App\Contracts\GameServer\GameServerGateway;
use App\Models\GameDataCatalog;
use App\Models\GameNpc;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class AdventureWorldControllerTest extends TestCase
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
    }

    public function test_npc_catalog_is_paginated_and_hides_invisible_npcs(): void
    {
        $catalog = GameDataCatalog::factory()->create(['resource_type' => 'npcs']);
        GameNpc::factory()->count(40)->create(['game_data_catalog_id' => $catalog->id]);
        GameNpc::factory()->create(['game_data_catalog_id' => $catalog->id, 'npc_key' => 'prontera:9:9:Hidden', 'game_visible' => false]);

        $response = $this->withHeaders($this->headers())->getJson('/api/adventure-tools/npcs?perPage=32');

        $response->assertOk()->assertJsonPath('total', 40)->assertJsonCount(32, 'data');
    }

    public function test_npc_catalog_can_be_scoped_to_the_current_map(): void
    {
        $catalog = GameDataCatalog::factory()->create(['resource_type' => 'npcs']);
        GameNpc::factory()->create(['game_data_catalog_id' => $catalog->id, 'npc_key' => 'prontera:1:1:A']);
        GameNpc::factory()->create(['game_data_catalog_id' => $catalog->id, 'npc_key' => 'izlude:1:1:B', 'map' => 'izlude']);
        GameNpc::factory()->create(['game_data_catalog_id' => $catalog->id, 'npc_key' => 'izlude_a:1:1:C', 'map' => 'izlude_a']);

        $this->withHeaders($this->headers())->getJson('/api/adventure-tools/npcs?onMap=izlude')
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.map', 'izlude');
    }

    public function test_map_npcs_returns_the_whole_map_for_preview_markers(): void
    {
        $catalog = GameDataCatalog::factory()->create(['resource_type' => 'npcs']);
        GameNpc::factory()->count(5)->create(['game_data_catalog_id' => $catalog->id]);

        $this->withHeaders($this->headers())->getJson('/api/adventure-tools/maps/prontera/npcs')
            ->assertOk()->assertJsonPath('total', 5)->assertJsonCount(5, 'data');
    }

    public function test_map_catalog_is_paginated_and_searchable(): void
    {
        $this->mock(GameServerGateway::class)->shouldReceive('battleConfig')->andReturn(['navigation_map_channels_enabled' => 0]);

        $response = $this->withHeaders($this->headers())->getJson('/api/adventure-tools/maps?perPage=35');
        $response->assertOk()->assertJsonCount(35, 'data');
        $this->assertGreaterThan(35, $response->json('total'));

        $this->withHeaders($this->headers())->getJson('/api/adventure-tools/maps?query=prontera')
            ->assertOk()->assertJsonPath('data.0.map', 'prontera');
    }

    public function test_hidden_channel_current_map_is_prioritized_through_the_adventure_api(): void
    {
        $this->mock(GameServerGateway::class)->shouldReceive('battleConfig')->andReturn(['navigation_map_channels_enabled' => 0]);

        $this->withHeaders($this->headers())->getJson('/api/adventure-tools/maps?currentMap=izlude_a&perPage=1')
            ->assertOk()->assertJsonPath('data.0.map', 'izlude');
    }

    public function test_npc_search_matches_number_and_navigation_alias_and_prioritizes_current_map(): void
    {
        $catalog = GameDataCatalog::factory()->create(['resource_type' => 'npcs']);
        GameNpc::factory()->create([
            'game_data_catalog_id' => $catalog->id, 'npc_key' => 'iz_int:56:32:Swordsman',
            'map' => 'iz_int', 'display_name' => '受伤的剑士', 'payload' => ['navigation' => ['class' => 687]],
        ]);
        GameNpc::factory()->create([
            'game_data_catalog_id' => $catalog->id, 'npc_key' => 'prontera:1:1:A',
            'display_name' => '卡普拉', 'catalog_order' => 0,
            'payload' => ['navigation' => ['name' => '카프라 직원', 'class' => 112]],
        ]);
        GameNpc::factory()->create([
            'game_data_catalog_id' => $catalog->id, 'npc_key' => 'geffen:1:1:B',
            'map' => 'geffen', 'display_name' => '卡普拉', 'catalog_order' => 99,
            'payload' => ['navigation' => ['name' => '카프라 직원', 'class' => 112]],
        ]);

        $this->withHeaders($this->headers())->getJson('/api/adventure-tools/npcs?query=687')
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.display_name', '受伤的剑士');
        $this->withHeaders($this->headers())->getJson('/api/adventure-tools/npcs?query=Kafra&currentMap=geffen&perPage=1')
            ->assertOk()->assertJsonPath('total', 2)->assertJsonPath('data.0.map', 'geffen');
        $this->withHeaders($this->headers())->getJson('/api/adventure-tools/npcs?query=不存在的名字')
            ->assertOk()->assertJsonPath('total', 0)->assertJsonCount(0, 'data');
    }

    public function test_navigation_alias_exact_match_precedes_name_substring_match(): void
    {
        $catalog = GameDataCatalog::factory()->create(['resource_type' => 'npcs']);
        GameNpc::factory()->create([
            'game_data_catalog_id' => $catalog->id, 'npc_key' => 'alias',
            'display_name' => '卡普拉', 'payload' => ['navigation' => ['name' => '카프라 직원']],
        ]);
        GameNpc::factory()->create([
            'game_data_catalog_id' => $catalog->id, 'npc_key' => 'substring',
            'display_name' => 'A Kafra employee',
        ]);

        $this->withHeaders($this->headers())->getJson('/api/adventure-tools/npcs?query=Kafra&perPage=1')
            ->assertOk()->assertJsonPath('total', 2)->assertJsonPath('data.0.id', 'alias');
    }

    public function test_adventure_map_search_ranks_exact_match_before_substring_before_pagination(): void
    {
        $this->mock(GameServerGateway::class)->shouldReceive('battleConfig')->andReturn(['navigation_map_channels_enabled' => 0]);

        $this->withHeaders($this->headers())->getJson('/api/adventure-tools/maps?query=彩虹桥&perPage=1')
            ->assertOk()->assertJsonPath('data.0.map', 'bif_fild01');
    }

    public function test_world_catalogs_require_a_game_session(): void
    {
        $this->getJson('/api/adventure-tools/npcs')->assertUnauthorized();
        $this->getJson('/api/adventure-tools/maps')->assertUnauthorized();
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        return ['X-HappyRO-Account-ID' => '2000001', 'X-HappyRO-Character-ID' => '150002', 'X-HappyRO-Auth-Token' => 'token'];
    }
}
