<?php

namespace Tests\Feature\GameData;

use App\Contracts\GameData\MonsterSnapshotReader;
use App\Data\GameData\MonsterCatalogSnapshot;
use App\Services\GameData\ImportMonstersService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

final class ImportMonstersTest extends TestCase
{
    use RefreshDatabase;

    public function test_reimport_is_idempotent_and_removes_stale_monsters(): void
    {
        $reader = Mockery::mock(MonsterSnapshotReader::class);
        $reader->expects('read')->twice()->andReturn(
            $this->snapshot([1001 => $this->monster('蝎子'), 1002 => $this->monster('波利')]),
            $this->snapshot([1002 => $this->monster('波利（新）')], 'hash2'),
        );
        $service = new ImportMonstersService($reader, app('db'));
        $service->import('monsters.json');
        $result = $service->import('monsters.json');

        $this->assertSame(1, $result['imported']);
        $this->assertSame(1, $result['deleted']);
        $this->assertDatabaseCount('game_monsters', 1);
        $this->assertDatabaseHas('game_monsters', ['monster_id' => 1002, 'name_zh_cn' => '波利（新）']);
    }

    /** @param array<int, array<string, mixed>> $monsters */
    private function snapshot(array $monsters, string $hash = 'hash1'): MonsterCatalogSnapshot
    {
        return new MonsterCatalogSnapshot('server1', $hash, [], $monsters);
    }

    /** @return array<string, mixed> */
    private function monster(string $name): array
    {
        return ['AegisName' => 'TEST', 'names' => ['zh-CN' => $name, 'en-US' => 'Test']];
    }
}
