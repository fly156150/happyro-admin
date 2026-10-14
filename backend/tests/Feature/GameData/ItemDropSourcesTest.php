<?php

namespace Tests\Feature\GameData;

use App\Contracts\GameData\ItemAssetRepository;
use App\Contracts\GameData\ItemViewBuilder;
use App\Contracts\GameData\MonsterCatalogRepository;
use App\Data\GameData\MonsterCatalogSnapshot;
use App\Models\GameDataCatalog;
use App\Models\GameItem;
use App\Models\GameItemSource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

final class ItemDropSourcesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_details_merge_methods_and_filter_before_pagination_without_duplicates(): void
    {
        $this->items();
        $this->import('server1', [
            1002 => $this->monster('波利', ['Drops' => [$this->drop('Item_501'), $this->drop('Item_501'), $this->drop('Item_502')]]),
            1096 => $this->monster('天使波利', ['Class' => 'Boss', 'Drops' => [$this->drop('Item_501')]]),
            1039 => $this->monster('巴风特', ['Level' => 77, 'Drops' => [$this->drop('Item_501')], 'MvpDrops' => [$this->drop('Item_501')]]),
        ]);
        $this->import('other-version', [1001 => $this->monster('旧版魔物', ['Drops' => [$this->drop('Item_503')]])]);
        $this->authenticate();
        $this->getJson('/api/game-data/items/501')->assertOk()->assertJsonPath('data.dropSources', [
            ['id' => 1002, 'name' => '波利', 'kind' => 'normal', 'level' => 1, 'drops' => [['method' => 'normal', 'rate' => 100, 'count' => 2]]],
            ['id' => 1039, 'name' => '巴风特', 'kind' => 'mvp', 'level' => 77, 'drops' => [['method' => 'mvp_reward', 'rate' => 100, 'count' => 1], ['method' => 'normal', 'rate' => 100, 'count' => 1]]],
            ['id' => 1096, 'name' => '天使波利', 'kind' => 'mini', 'level' => 1, 'drops' => [['method' => 'normal', 'rate' => 100, 'count' => 1]]],
        ]);
        $this->getJson('/api/game-data/items?dropKind=normal&perPage=1&page=2')
            ->assertOk()->assertJsonPath('total', 2)->assertJsonCount(1, 'data')->assertJsonPath('data.0.Id', 502);
        foreach (['mini', 'mvp'] as $kind) {
            $this->getJson('/api/game-data/items?dropKind='.$kind)->assertOk()
                ->assertJsonPath('total', 1)->assertJsonPath('data.0.Id', 501);
        }
        $this->getJson('/api/game-data/items?dropKind=mvp&query=502')->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/game-data/items?dropKind=all')->assertOk()->assertJsonPath('total', 3);
        $this->getJson('/api/game-data/items/503')->assertOk()->assertJsonPath('data.dropSources', []);
        $this->getJson('/api/game-data/items?dropKind=invalid')->assertUnprocessable()->assertJsonValidationErrors('dropKind');
    }

    public function test_reimport_replaces_sources_and_ignores_zero_rate_drops(): void
    {
        $this->items();
        $this->import('server1', [1002 => $this->monster('波利', ['Drops' => [$this->drop('Item_501')]])]);
        $this->import('server1', [1096 => $this->monster('天使波利', [
            'Class' => 'Boss', 'Drops' => [$this->drop('Item_502'), ['Item' => 'Item_501', 'Rate' => 0]],
        ])]);
        $this->authenticate();
        $this->getJson('/api/game-data/items/501')->assertOk()->assertJsonPath('data.dropSources', []);
        $this->getJson('/api/game-data/items/502')->assertOk()->assertJsonPath('data.dropSources.0.id', 1096);
        $this->assertDatabaseCount('game_monster_drops', 1);
        $this->import('server1', [1096 => $this->monster('天使波利', ['Drops' => [$this->drop('Item_503')]])]);
        $this->getJson('/api/game-data/items?dropKind=mini')->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/game-data/items?dropKind=normal')->assertOk()->assertJsonPath('data.0.Id', 503);
    }

    public function test_failed_import_rolls_back_monsters_and_drop_relations_together(): void
    {
        $this->items();
        $this->import('server1', [1002 => $this->monster('波利', ['Drops' => [$this->drop('Item_501')]])]);
        try {
            $this->import('server1', [1002 => $this->monster('波利', ['Drops' => [['Item' => 'Item_502', 'Rate' => 1]]]), 'bad' => []]);
            $this->fail('Invalid import should fail');
        } catch (\RuntimeException) {
            $this->assertDatabaseHas('game_monster_drops', ['item_aegis_name' => 'Item_501']);
            $this->assertDatabaseMissing('game_monster_drops', ['item_aegis_name' => 'Item_502']);
        }
    }

    private function items(): void
    {
        config(['happyro.game_data.default_client_version' => 'client1', 'happyro.game_data.default_server_version' => 'server1']);
        $client = GameDataCatalog::factory()->create(['source' => 'client', 'ruleset' => 'client', 'source_version' => 'client1']);
        $server = GameDataCatalog::factory()->create(['source_version' => 'server1']);
        foreach ([501, 502, 503] as $id) {
            $item = GameItem::factory()->create(['item_id' => $id]);
            foreach ([$client, $server] as $catalog) {
                GameItemSource::factory()->create([
                    'game_item_id' => $item->id, 'game_data_catalog_id' => $catalog->id,
                    'aegis_name' => 'Item_'.$id, 'item_type' => 'Healing',
                ]);
            }
        }
        app(ItemViewBuilder::class)->rebuildAll();
    }

    private function authenticate(): void
    {
        $this->mock(ItemAssetRepository::class)->shouldReceive('iconPath', 'illustrationPath')->andReturnNull();
        $user = User::factory()->create();
        $role = Role::query()->firstOrCreate(['name' => 'super_admin'], ['label' => '超级管理员']);
        $user->roles()->attach($role);
        $this->actingAs($user);
    }

    private function import(string $version, array $monsters): void
    {
        app(MonsterCatalogRepository::class)->replace(new MonsterCatalogSnapshot($version, hash('sha256', json_encode($monsters)), [], $monsters));
    }

    private function monster(string $name, array $data): array
    {
        return ['AegisName' => 'TEST', 'names' => ['zh-CN' => $name, 'en-US' => 'Test'], ...$data];
    }

    private function drop(string $name): array
    {
        return ['Item' => $name, 'Rate' => 100];
    }
}
