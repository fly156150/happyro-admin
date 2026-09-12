<?php

namespace Tests\Feature\GameData;

use App\Contracts\GameData\ItemCatalogRepository;
use App\Contracts\GameData\ItemSnapshotReader;
use App\Contracts\GameData\ItemViewBuilder;
use App\Data\GameData\ItemCatalogSnapshot;
use App\Services\GameData\ImportItemsService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

final class ImportItemsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_reimport_is_idempotent_and_removes_stale_items(): void
    {
        $reader = Mockery::mock(ItemSnapshotReader::class);
        $reader->expects('read')->times(2)->andReturn(
            $this->snapshot([501 => $this->item('红色药水', 'Red Potion'), 502 => $this->item('橙色药水', 'Orange Potion')]),
            $this->snapshot([501 => $this->item('红色药水（新）', 'Red Potion')], 'hash-2'),
        );
        $views = Mockery::mock(ItemViewBuilder::class);
        $views->expects('rebuildAll')->twice()->andReturn(['combinations' => 0, 'records' => 0]);
        $service = new ImportItemsService($reader, $views, app(ItemCatalogRepository::class));

        $first = $service->import('snapshot.json');
        $second = $service->import('snapshot.json');

        $this->assertSame(2, $first['imported']);
        $this->assertSame(1, $second['imported']);
        $this->assertSame(1, $second['deleted']);
        $this->assertDatabaseCount('game_data_catalogs', 1);
        $this->assertDatabaseCount('game_items', 1);
        $this->assertDatabaseCount('game_item_sources', 1);
        $this->assertDatabaseHas('game_items', ['item_id' => 501]);
        $this->assertDatabaseHas('game_item_sources', ['name_zh_cn' => '红色药水（新）']);
    }

    public function test_command_without_source_only_displays_usage(): void
    {
        $this->artisan('game-data:import-items', ['--no-color' => true])
            ->expectsOutputToContain('HappyRO 游戏资料导入')
            ->assertSuccessful();

        $this->assertDatabaseCount('game_data_catalogs', 0);
    }

    public function test_import_rejects_an_empty_snapshot(): void
    {
        $reader = Mockery::mock(ItemSnapshotReader::class);
        $reader->expects('read')->once()->andReturn($this->snapshot([]));
        $views = Mockery::mock(ItemViewBuilder::class);
        $views->expects('rebuildAll')->never();

        $this->expectException(RuntimeException::class);
        (new ImportItemsService($reader, $views, app(ItemCatalogRepository::class)))->import('snapshot.json');
    }

    public function test_import_rebuilds_merged_query_views(): void
    {
        $reader = Mockery::mock(ItemSnapshotReader::class);
        $reader->expects('read')->twice()->andReturn(
            $this->snapshot([501 => $this->item('客户端红药', 'Red Potion')]),
            new ItemCatalogSnapshot('server', 'renewal', 'server1', 'server-hash', [], [
                501 => [
                    'names' => ['zh-CN' => '服务端红药', 'en-US' => 'Red Potion'],
                    'AegisName' => 'Red_Potion',
                    'Type' => 'Armor',
                    'Locations' => ['Head_Top' => true],
                    'Weight' => 70,
                ],
            ]),
        );
        $service = new ImportItemsService($reader, app(ItemViewBuilder::class), app(ItemCatalogRepository::class));

        $results = $service->importMany(['client.json', 'server.json']);
        $result = $results[1];

        $this->assertCount(2, $results);
        $this->assertSame(1, $result['views']);
        $this->assertDatabaseCount('game_items', 1);
        $this->assertDatabaseCount('game_item_sources', 2);
        $this->assertDatabaseHas('game_item_views', [
            'item_id' => 501,
            'client_exists' => true,
            'server_exists' => true,
            'name_zh_cn' => '客户端红药',
            'aegis_name' => 'Red_Potion',
            'item_type' => 'Armor',
            'item_subtype' => 'Head_Top',
            'weight' => 70,
        ]);
    }

    /** @param array<int, array<string, mixed>> $items */
    private function snapshot(array $items, string $hash = 'hash-1'): ItemCatalogSnapshot
    {
        return new ItemCatalogSnapshot('client', 'client', 'kro-20211105', $hash, [], $items);
    }

    /** @return array<string, mixed> */
    private function item(string $chinese, string $english): array
    {
        return [
            'names' => ['zh-CN' => $chinese, 'en-US' => $english],
            'identifiedResourceName' => 'potion',
            'identifiedDescriptionName' => ['^000088恢复 HP^000000', '^ffffff_^000000'],
        ];
    }
}
