<?php

namespace Tests\Feature\GameData;

use App\Models\GameDataCatalog;
use App\Models\GameNpc;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

final class ListNpcsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_npc_api_paginates_instead_of_returning_the_whole_catalog(): void
    {
        $catalog = $this->catalog();
        GameNpc::factory()->count(25)->create(['game_data_catalog_id' => $catalog->id]);

        $response = $this->actingAs($this->operator())->getJson('/api/game-data/npcs?perPage=10&page=2');

        $response->assertOk()->assertJsonPath('total', 25)->assertJsonCount(10, 'data');
    }

    public function test_npc_api_filters_by_visibility_map_and_name(): void
    {
        $catalog = $this->catalog();
        GameNpc::factory()->create([
            'game_data_catalog_id' => $catalog->id, 'npc_key' => 'prontera:1:1:Guide',
            'name' => 'Guide#prt', 'source_name' => 'Guide', 'display_name' => '向导',
        ]);
        GameNpc::factory()->create([
            'game_data_catalog_id' => $catalog->id, 'npc_key' => 'geffen:2:2:Hidden',
            'map' => 'geffen', 'map_name_zh_cn' => '吉芬', 'display_name' => '隐藏', 'game_visible' => false,
        ]);

        $this->actingAs($this->operator());
        $this->getJson('/api/game-data/npcs')->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/game-data/npcs?visibility=all')->assertOk()->assertJsonPath('total', 2);
        $this->getJson('/api/game-data/npcs?visibility=all&map=吉芬')->assertOk()
            ->assertJsonPath('total', 1)->assertJsonPath('data.0.map', 'geffen');
        $this->getJson('/api/game-data/npcs?name_zh_cn=向导')->assertOk()
            ->assertJsonPath('total', 1)->assertJsonPath('data.0.name_zh_cn', '向导');
    }

    public function test_map_npcs_endpoint_returns_every_npc_on_one_map(): void
    {
        $catalog = $this->catalog();
        GameNpc::factory()->count(3)->create(['game_data_catalog_id' => $catalog->id]);
        GameNpc::factory()->create(['game_data_catalog_id' => $catalog->id, 'npc_key' => 'geffen:9:9:Other', 'map' => 'geffen']);

        $response = $this->actingAs($this->operator())->getJson('/api/game-data/maps/prontera/npcs');

        $response->assertOk()->assertJsonPath('total', 3)->assertJsonCount(3, 'data');
        $this->assertSame(['prontera', 'prontera', 'prontera'], array_column($response->json('data'), 'map'));
    }

    public function test_map_npcs_hide_entries_that_are_invisible_in_game_by_default(): void
    {
        $catalog = $this->catalog();
        GameNpc::factory()->create(['game_data_catalog_id' => $catalog->id, 'npc_key' => 'prontera:1:1:Shown']);
        GameNpc::factory()->create(['game_data_catalog_id' => $catalog->id, 'npc_key' => 'prontera:2:2:Hidden', 'game_visible' => false]);

        $this->actingAs($this->operator());
        $this->getJson('/api/game-data/maps/prontera/npcs')->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/game-data/maps/prontera/npcs?visibility=all')->assertOk()->assertJsonPath('total', 2);
    }

    public function test_npc_image_url_comes_from_the_catalog_flag(): void
    {
        $catalog = $this->catalog();
        GameNpc::factory()->create(['game_data_catalog_id' => $catalog->id, 'npc_key' => 'prontera:3:3:NoImage', 'image_available' => false]);

        $this->actingAs($this->operator())->getJson('/api/game-data/npcs')
            ->assertOk()->assertJsonPath('data.0.image', null);
    }

    public function test_npc_api_requires_authentication(): void
    {
        $this->getJson('/api/game-data/npcs')->assertUnauthorized();
    }

    private function catalog(): GameDataCatalog
    {
        return GameDataCatalog::factory()->create(['resource_type' => 'npcs', 'source_version' => 'kro-test']);
    }

    private function operator(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'super_admin'], ['label' => '超级管理员']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
