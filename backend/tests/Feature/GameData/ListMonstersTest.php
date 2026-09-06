<?php

namespace Tests\Feature\GameData;

use App\Contracts\GameData\MonsterAssetRepository;
use App\Models\GameDataCatalog;
use App\Models\GameDataSetting;
use App\Models\GameMonster;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

final class ListMonstersTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_monster_api_searches_and_filters_catalog(): void
    {
        $catalog = GameDataCatalog::factory()->create(['resource_type' => 'monsters', 'source_version' => 'server1']);
        GameDataSetting::query()->whereKey(1)->update(['server_version' => 'server1']);
        GameMonster::factory()->create(['game_data_catalog_id' => $catalog->id, 'monster_id' => 1002, 'aegis_name' => 'PORING', 'name_zh_cn' => '波利']);
        GameMonster::factory()->create(['game_data_catalog_id' => $catalog->id, 'monster_id' => 1039, 'aegis_name' => 'BAPHOMET', 'name_zh_cn' => '巴风特', 'race' => 'Demon', 'is_boss' => true]);
        $otherCatalog = GameDataCatalog::factory()->create(['resource_type' => 'monsters', 'source_version' => 'server2']);
        GameMonster::factory()->create(['game_data_catalog_id' => $otherCatalog->id, 'monster_id' => 2000]);
        $this->mock(MonsterAssetRepository::class)->shouldReceive('imagePath')->andReturnNull();
        $role = Role::query()->firstOrCreate(['name' => 'super_admin'], ['label' => '超级管理员']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->actingAs($user)->getJson('/api/game-data/monsters?query=巴风&race=Demon&boss=1')
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.Id', 1039);
        $this->getJson('/api/game-data/monsters/1039')
            ->assertOk()->assertJsonPath('data.names.zh-CN', '巴风特');
        $this->getJson('/api/game-data/monsters?serverVersion=server2')
            ->assertOk()->assertJsonPath('total', 2);
    }

    public function test_monster_api_requires_authentication(): void
    {
        $this->getJson('/api/game-data/monsters')->assertUnauthorized();
    }
}
