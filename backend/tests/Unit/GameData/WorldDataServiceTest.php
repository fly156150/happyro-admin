<?php

namespace Tests\Unit\GameData;

use App\Services\GameData\WorldDataService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class WorldDataServiceTest extends TestCase
{
    public function test_empty_map_placeholder_is_not_exposed_as_an_image(): void
    {
        $maps = collect((new WorldDataService)->maps())->keyBy('map');

        $this->assertSame('terrain', $maps->get('alb_ship')['image_kind']);
        $this->assertNotNull($maps->get('alb_ship')['image']);
        $this->assertNotNull($maps->get('prontera')['image']);
        $this->assertNotNull($maps->get('new_1-1'));
        $this->assertNotNull($maps->get('1@nyd'));
    }

    public function test_reads_maps_and_shared_npc_catalog_metadata(): void
    {
        $root = storage_path('framework/testing/world-data');
        File::deleteDirectory($root);
        File::ensureDirectoryExists($root);
        File::put($root.'/map_index.txt', "prontera 0\n// comment\nprt_fild01\nnew_1-1\n1@nyd\n");
        File::put($root.'/npc-catalog.json', json_encode([
            'schema' => 'happyro-npc-catalog/v1',
            'entries' => [[
                'id' => 'prontera:100:101:Guide Keeper#01',
                'map' => 'prontera',
                'map_name_zh_cn' => '普隆德拉',
                'x' => 100,
                'y' => 101,
                'name' => 'Guide Keeper#01',
                'source_name' => 'Guide Keeper',
                'display_name' => '向导',
                'type' => 'script',
                'sprite_id' => 419,
                'display_sprite_id' => 419,
                'enabled' => true,
                'game_visible' => true,
                'catalog_order' => 0,
                'navigation' => ['id' => 10, 'class' => 419],
                'source' => ['path' => 'npc/test.txt', 'line' => 7],
            ]],
        ], JSON_THROW_ON_ERROR));
        File::put($root.'/maps-placeholder', '');
        config([
            'happyro.game_data.map_index_path' => $root.'/map_index.txt',
            'happyro.game_data.npc_catalog_path' => $root.'/npc-catalog.json',
        ]);

        $service = new WorldDataService;

        $this->assertSame(['prontera', 'prt_fild01', 'new_1-1', '1@nyd'], array_column($service->maps(), 'map'));
        $npc = $service->npcs()[0];
        $this->assertSame('Guide Keeper#01', $npc['name']);
        $this->assertSame('向导', $npc['name_zh_cn']);
        $this->assertSame(419, $npc['sprite_id']);
        $this->assertTrue($npc['game_visible']);
        $this->assertSame(0, $npc['catalog_order']);
        $this->assertSame('npc/test.txt', $npc['source']['path']);

        File::deleteDirectory($root);
    }

    public function test_returns_no_npcs_for_an_invalid_catalog(): void
    {
        $path = storage_path('framework/testing/invalid-npc-catalog.json');
        File::put($path, '{"schema":"unsupported"}');
        config(['happyro.game_data.npc_catalog_path' => $path]);

        $this->assertSame([], (new WorldDataService)->npcs());

        File::delete($path);
    }
}
