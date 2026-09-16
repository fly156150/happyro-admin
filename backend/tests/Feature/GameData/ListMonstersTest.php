<?php

namespace Tests\Feature\GameData;

use App\Contracts\GameData\MonsterAssetRepository;
use App\Models\GameDataCatalog;
use App\Models\GameItem;
use App\Models\GameItemView;
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
        $catalog = GameDataCatalog::factory()->create([
            'resource_type' => 'monsters',
            'source_version' => config('happyro.game_data.default_server_version'),
        ]);
        GameMonster::factory()->create(['game_data_catalog_id' => $catalog->id, 'monster_id' => 1002, 'aegis_name' => 'PORING', 'name_zh_cn' => '波利']);
        GameMonster::factory()->create(['game_data_catalog_id' => $catalog->id, 'monster_id' => 2401, 'aegis_name' => 'G_PORING', 'name_zh_cn' => '波利']);
        GameMonster::factory()->create(['game_data_catalog_id' => $catalog->id, 'monster_id' => 1096, 'aegis_name' => 'ANGELING', 'name_zh_cn' => '天使波利', 'kind' => 'mini']);
        GameMonster::factory()->create([
            'game_data_catalog_id' => $catalog->id, 'monster_id' => 1039, 'aegis_name' => 'BAPHOMET',
            'name_zh_cn' => '巴风特', 'race' => 'Demon', 'kind' => 'mvp',
            'payload' => ['MvpDrops' => [['Item' => 'Baphomet_Card', 'Rate' => 1]]],
        ]);
        $otherCatalog = GameDataCatalog::factory()->create(['resource_type' => 'monsters', 'source_version' => 'server2']);
        GameMonster::factory()->create(['game_data_catalog_id' => $otherCatalog->id, 'monster_id' => 2000]);
        $this->mock(MonsterAssetRepository::class)->shouldReceive('imagePath')->andReturnNull();
        $role = Role::query()->firstOrCreate(['name' => 'super_admin'], ['label' => '超级管理员']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->actingAs($user)->getJson('/api/game-data/monsters?query=波利')
            ->assertOk()->assertJsonPath('total', 3)
            ->assertJsonPath('data.0.Id', 1002)
            ->assertJsonPath('data.1.Id', 1096)
            ->assertJsonPath('data.2.Id', 2401);
        $this->getJson('/api/game-data/monsters?query=巴风&race=Demon&kind=mvp')
            ->assertOk()->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.Id', 1039)
            ->assertJsonPath('data.0.kind', 'mvp');
        $this->getJson('/api/game-data/monsters?kind=mini')
            ->assertOk()->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.Id', 1096)
            ->assertJsonPath('data.0.kind', 'mini');
        $this->getJson('/api/game-data/monsters?kind=normal&query=波利')
            ->assertOk()->assertJsonPath('total', 2)
            ->assertJsonPath('data.0.kind', 'normal');
        $this->getJson('/api/game-data/monsters/1039')
            ->assertOk()->assertJsonPath('data.names.zh-CN', '巴风特')
            ->assertJsonPath('data.kind', 'mvp')
            ->assertJsonPath('data.MvpDrops.0.Item', 'Baphomet_Card')
            ->assertJsonPath('data.MvpDrops.0.names.zh-CN', 'Baphomet_Card');
        $this->getJson('/api/game-data/monsters?serverVersion=server2')
            ->assertOk()->assertJsonPath('total', 4);
        $this->getJson('/api/game-data/monsters?kind=invalid')
            ->assertUnprocessable();
    }

    public function test_monster_detail_localizes_drop_item_names(): void
    {
        $monsterCatalog = GameDataCatalog::factory()->create([
            'resource_type' => 'monsters',
            'source_version' => config('happyro.game_data.default_server_version'),
        ]);
        GameMonster::factory()->create([
            'game_data_catalog_id' => $monsterCatalog->id,
            'monster_id' => 1002,
            'aegis_name' => 'PORING',
            'name_zh_cn' => '波利',
            'payload' => [
                'Drops' => [
                    ['Item' => 'Jellopy', 'Rate' => 7000],
                    ['Item' => 'Unknown_Drop', 'Rate' => 10],
                ],
                'MvpDrops' => [['Item' => 'Poring_Card', 'Rate' => 1]],
            ],
        ]);
        $this->itemView('Jellopy', 909, '杰勒比结晶', 'Jellopy');
        $this->itemView('Poring_Card', 4001, '波利卡片', 'Poring Card');
        $this->mock(MonsterAssetRepository::class)->shouldReceive('imagePath')->andReturnNull();
        $this->actingAs($this->superAdmin());

        $this->getJson('/api/game-data/monsters/1002')
            ->assertOk()
            ->assertJsonPath('data.Drops.0.Item', 'Jellopy')
            ->assertJsonPath('data.Drops.0.itemId', 909)
            ->assertJsonPath('data.Drops.0.names.zh-CN', '杰勒比结晶')
            ->assertJsonPath('data.Drops.0.names.en-US', 'Jellopy')
            ->assertJsonPath('data.Drops.1.Item', 'Unknown_Drop')
            ->assertJsonPath('data.Drops.1.itemId', 0)
            ->assertJsonPath('data.Drops.1.names.zh-CN', 'Unknown_Drop')
            ->assertJsonPath('data.MvpDrops.0.itemId', 4001)
            ->assertJsonPath('data.MvpDrops.0.names.zh-CN', '波利卡片');
        $this->getJson('/api/game-data/monsters?query=波利')
            ->assertOk()
            ->assertJsonPath('data.0.Drops.0.Item', 'Jellopy')
            ->assertJsonMissingPath('data.0.Drops.0.names');
    }

    public function test_monster_api_requires_authentication(): void
    {
        $this->getJson('/api/game-data/monsters')->assertUnauthorized();
    }

    private function itemView(string $aegisName, int $itemId, string $zh, string $en): void
    {
        $client = GameDataCatalog::query()->firstOrCreate(
            [
                'resource_type' => 'items',
                'source' => 'client',
                'ruleset' => 'client',
                'source_version' => config('happyro.game_data.default_client_version'),
            ],
            [
                'content_hash' => 'client-hash',
                'record_count' => 0,
                'source_metadata' => [],
                'imported_at' => now(),
            ],
        );
        $server = GameDataCatalog::query()->firstOrCreate(
            [
                'resource_type' => 'items',
                'source' => 'server',
                'ruleset' => 'renewal',
                'source_version' => config('happyro.game_data.default_server_version'),
            ],
            [
                'content_hash' => 'server-hash',
                'record_count' => 0,
                'source_metadata' => [],
                'imported_at' => now(),
            ],
        );
        $item = GameItem::query()->firstOrCreate(['item_id' => $itemId]);
        GameItemView::query()->create([
            'game_item_id' => $item->id,
            'item_id' => $itemId,
            'client_catalog_id' => $client->id,
            'server_catalog_id' => $server->id,
            'client_exists' => true,
            'server_exists' => true,
            'name_zh_cn' => $zh,
            'name_en_us' => $en,
            'aegis_name' => $aegisName,
            'item_type' => 'Etc',
            'field_sources' => [],
            'payload' => [],
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
