<?php

namespace Tests\Feature\GameData;

use App\Contracts\GameData\NpcCatalogRepository;
use App\Contracts\GameData\NpcSnapshotReader;
use App\Data\GameData\NpcCatalogSnapshot;
use App\Services\GameData\ImportNpcsService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery;
use Tests\TestCase;

final class ImportNpcsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_command_without_source_only_displays_usage(): void
    {
        $reader = Mockery::mock(NpcSnapshotReader::class);
        $reader->expects('read')->never();
        $this->app->instance(NpcSnapshotReader::class, $reader);

        $this->artisan('game-data:import-npcs', ['--no-color' => true])
            ->expectsOutputToContain('HappyRO NPC 目录导入')
            ->expectsOutputToContain('php artisan game-data:import-npcs --renewal')
            ->assertSuccessful();

        $this->assertDatabaseCount('game_data_catalogs', 0);
    }

    public function test_reimport_is_idempotent_and_removes_stale_npcs(): void
    {
        $reader = Mockery::mock(NpcSnapshotReader::class);
        $reader->expects('read')->twice()->andReturn(
            $this->snapshot([$this->npc('prontera:1:1:Guide', '向导'), $this->npc('geffen:2:2:Sage', '贤者')]),
            $this->snapshot([$this->npc('geffen:2:2:Sage', '贤者（新）')], 'hash2'),
        );
        $service = new ImportNpcsService($reader, app(NpcCatalogRepository::class));
        $service->import('npc-catalog.json');
        $result = $service->import('npc-catalog.json');

        $this->assertSame(1, $result['imported']);
        $this->assertSame(1, $result['deleted']);
        $this->assertDatabaseCount('game_npcs', 1);
        $this->assertDatabaseHas('game_npcs', ['npc_key' => 'geffen:2:2:Sage', 'display_name' => '贤者（新）']);
    }

    public function test_catalog_columns_keep_the_generated_image_flag(): void
    {
        $reader = Mockery::mock(NpcSnapshotReader::class);
        $reader->expects('read')->once()->andReturn($this->snapshot([
            [...$this->npc('prontera:1:1:Ghost', '幽灵'), 'image_available' => false, 'display_sprite_id' => null],
        ]));
        (new ImportNpcsService($reader, app(NpcCatalogRepository::class)))->import('npc-catalog.json');

        $this->assertDatabaseHas('game_npcs', ['npc_key' => 'prontera:1:1:Ghost', 'image_available' => false]);
    }

    /** @param list<array<string, mixed>> $npcs */
    private function snapshot(array $npcs, string $hash = 'hash1'): NpcCatalogSnapshot
    {
        return new NpcCatalogSnapshot('kro-test', $hash, ['schema' => 'happyro-npc-catalog/v1'], $npcs);
    }

    /** @return array<string, mixed> */
    private function npc(string $id, string $displayName): array
    {
        [$map, $x, $y] = explode(':', $id);

        return [
            'id' => $id, 'map' => $map, 'x' => (int) $x, 'y' => (int) $y,
            'direction' => 0, 'type' => 'script', 'name' => $displayName, 'source_name' => 'Source',
            'display_name' => $displayName, 'sprite_id' => 419, 'sprite_key' => '419',
            'enabled' => true, 'dynamic' => false, 'source' => ['path' => 'npc/test.txt', 'line' => 1],
            'navigation' => null, 'map_name_zh_cn' => '普隆德拉', 'image_available' => true,
            'display_sprite_id' => 419, 'capabilities' => ['can_route' => true, 'can_teleport_to_npc' => false],
            'game_visible' => true, 'catalog_order' => 1,
        ];
    }
}
