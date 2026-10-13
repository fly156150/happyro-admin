<?php

namespace Tests\Feature\GameData;

use App\Contracts\GameData\ItemAssetRepository;
use App\Contracts\GameData\ItemViewBuilder;
use App\Models\GameDataCatalog;
use App\Models\GameItem;
use App\Models\GameItemSource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

final class ListItemsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_item_api_returns_client_server_and_union_ranges(): void
    {
        $client = GameDataCatalog::factory()->create(['source' => 'client', 'ruleset' => 'client', 'source_version' => 'kro-20211105']);
        $server = GameDataCatalog::factory()->create(['source' => 'server', 'ruleset' => 'renewal', 'source_version' => 'server123']);
        config(['happyro.game_data.default_client_version' => 'kro-20211105', 'happyro.game_data.default_server_version' => 'server123']);
        $this->source($client, 501, [
            'name_zh_cn' => '客户端红药',
            'name_en_us' => 'Red Potion',
            'description' => ['恢复 HP'],
        ]);
        $this->source($client, 502, ['name_zh_cn' => '客户端橙药', 'name_en_us' => 'Orange Potion']);
        $this->source($server, 501, ['name_zh_cn' => '服务端红药', 'name_en_us' => 'Red Potion', 'aegis_name' => 'Red_Potion', 'item_type' => 'Weapon', 'item_subtype' => '1hSword', 'payload' => ['Weight' => 70, 'SubType' => '1hSword']]);
        $this->source($server, 503, ['name_zh_cn' => '服务端黄药', 'name_en_us' => 'Yellow Potion', 'item_type' => 'Healing']);
        app(ItemViewBuilder::class)->rebuildAll();
        $this->withoutAssets();
        $this->actingAs($this->superAdmin());

        $this->getJson('/api/game-data/items?range=client')->assertOk()->assertJsonPath('total', 2);
        $this->getJson('/api/game-data/items?range=server')->assertOk()->assertJsonPath('total', 2);
        $this->getJson('/api/game-data/items?range=all')
            ->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonPath('data.0.source', 'both')
            ->assertJsonPath('data.0.names.zh-CN', '客户端红药')
            ->assertJsonPath('data.0.description.0', '恢复 HP');

        $this->getJson('/api/game-data/items?range=client&type=Weapon&subtype=1hSword')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.Id', 501)
            ->assertJsonPath('data.0.AegisName', 'Red_Potion')
            ->assertJsonPath('data.0.Weight', 70)
            ->assertJsonPath('data.0.Type', 'Weapon')
            ->assertJsonPath('data.0.SubType', '1hSword');
    }

    public function test_item_api_filters_across_merged_sources(): void
    {
        $client = GameDataCatalog::factory()->create(['source' => 'client', 'ruleset' => 'client', 'source_version' => 'client1']);
        $server = GameDataCatalog::factory()->create(['source' => 'server', 'ruleset' => 'renewal', 'source_version' => 'server1']);
        config(['happyro.game_data.default_client_version' => 'client1', 'happyro.game_data.default_server_version' => 'server1']);
        $this->source($client, 501, ['name_zh_cn' => '特别红药', 'name_en_us' => 'Red Potion']);
        $this->source($server, 501, ['name_zh_cn' => '红色药水', 'name_en_us' => 'Red Potion', 'item_type' => 'Healing']);
        app(ItemViewBuilder::class)->rebuildAll();
        $this->withoutAssets();
        $this->actingAs($this->superAdmin());

        $this->getJson('/api/game-data/items?range=all&query=特别&type=Healing')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.Id', 501);
    }

    public function test_item_api_requires_authentication_and_valid_range(): void
    {
        $this->get('/api/game-data/items')->assertUnauthorized();
        $this->getJson('/api/game-data/items')->assertUnauthorized();

        $this->actingAs($this->superAdmin())
            ->getJson('/api/game-data/items?range=invalid')
            ->assertUnprocessable();
    }

    public function test_subtypes_follow_catalog_range_and_type_without_search_or_pagination(): void
    {
        $client = GameDataCatalog::factory()->create(['source' => 'client', 'ruleset' => 'client', 'source_version' => 'client1']);
        $server = GameDataCatalog::factory()->create(['source' => 'server', 'ruleset' => 'renewal', 'source_version' => 'server1']);
        config(['happyro.game_data.default_client_version' => 'client1', 'happyro.game_data.default_server_version' => 'server1']);
        foreach ([501 => 'Dagger', 502 => 'Bow', 503 => 'Dagger', 504 => null, 505 => ''] as $id => $subtype) {
            $this->source($client, $id, ['name_zh_cn' => "物品{$id}"]);
            $this->source($server, $id, ['item_type' => 'Weapon', 'item_subtype' => $subtype]);
        }
        $this->source($server, 506, ['item_type' => 'Weapon', 'item_subtype' => 'Rifle']);
        $this->source($client, 507, ['name_zh_cn' => '衣服']);
        $this->source($server, 507, ['item_type' => 'Armor', 'item_subtype' => 'Armor']);
        app(ItemViewBuilder::class)->rebuildAll();
        $this->withoutAssets();
        $this->actingAs($this->superAdmin());

        $this->getJson('/api/game-data/items?range=client&type=Weapon&perPage=1')
            ->assertOk()->assertJsonPath('subtypes', ['Bow', 'Dagger']);
        $this->getJson('/api/game-data/items?range=client&type=Weapon&subtype=Dagger&query=absent')
            ->assertOk()->assertJsonPath('total', 0)->assertJsonPath('subtypes', ['Bow', 'Dagger']);
        $this->getJson('/api/game-data/items?range=server&type=Weapon')
            ->assertOk()->assertJsonPath('subtypes', ['Bow', 'Dagger', 'Rifle']);
        $this->getJson('/api/game-data/items?range=client&type=Armor')
            ->assertOk()->assertJsonPath('subtypes', ['Armor']);
        $this->getJson('/api/game-data/items?range=client&type=Healing')
            ->assertOk()->assertJsonPath('subtypes', []);
        $this->getJson('/api/game-data/items?range=client')
            ->assertOk()->assertJsonPath('subtypes', []);
    }

    private function withoutAssets(): void
    {
        $this->mock(ItemAssetRepository::class)
            ->shouldReceive('iconPath', 'illustrationPath')
            ->andReturnNull();
    }

    /** @param array<string, mixed> $attributes */
    private function source(GameDataCatalog $catalog, int $itemId, array $attributes): GameItemSource
    {
        $item = GameItem::query()->firstOrCreate(['item_id' => $itemId]);

        return GameItemSource::factory()->create([
            'game_item_id' => $item->id,
            'game_data_catalog_id' => $catalog->id,
            ...$attributes,
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
