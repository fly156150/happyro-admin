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

        $this->assertNull($maps->get('alb_ship')['image']);
        $this->assertNotNull($maps->get('prontera')['image']);
    }

    public function test_parses_map_rows_and_npc_sprite_metadata(): void
    {
        $root = storage_path('framework/testing/world-data');
        File::deleteDirectory($root);
        File::ensureDirectoryExists($root.'/npc');
        File::put($root.'/map_index.txt', "prontera 0\n// comment\nprt_fild01\n");
        File::put($root.'/npc/test.txt', "prontera,100,101,3\tscript\tGuide Keeper#01\t419,{ end; }\n");
        File::put($root.'/maps-placeholder', '');
        config(['happyro.game_data.map_index_path' => $root.'/map_index.txt', 'happyro.game_data.npc_root' => $root.'/npc']);

        $service = new WorldDataService;

        $this->assertSame('prontera', $service->maps()[0]['map']);
        $npc = $service->npcs()[0];
        $this->assertSame('Guide Keeper#01', $npc['name']);
        $this->assertSame(419, $npc['sprite_id']);

        File::deleteDirectory($root);
    }
}
