<?php

namespace App\Services\GameData;

use App\Contracts\GameData\GameDataSettingRepository;
use App\Models\GameDataCatalog;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;

final readonly class DatabaseItemDropRepository
{
    public function __construct(private DatabaseManager $database, private GameDataSettingRepository $settings) {}

    public function query(): Builder
    {
        $catalogId = GameDataCatalog::query()->where('resource_type', 'monsters')
            ->where('source', 'server')->where('ruleset', 'renewal')
            ->where('source_version', $this->settings->current()->server)->value('id');

        return $this->database->table('game_monster_drops as drops')
            ->join('game_monsters as monsters', 'monsters.id', '=', 'drops.game_monster_id')
            ->where('drops.game_data_catalog_id', $catalogId ?? 0);
    }

    /** @return list<array{id: int, name: string, kind: string, level: int, drops: list<array{method: string, rate: int, count: int}>}> */
    public function sources(string $aegisName): array
    {
        $rows = $this->query()->where('drops.item_aegis_name', $aegisName)
            ->select(['monsters.monster_id', 'monsters.name_zh_cn', 'monsters.name_en_us', 'monsters.aegis_name', 'monsters.kind', 'monsters.level', 'drops.method', 'drops.rate'])
            ->orderBy('monsters.monster_id')->orderBy('drops.method')->orderByDesc('drops.rate')->get();
        $sources = [];
        foreach ($rows as $row) {
            $id = (int) $row->monster_id;
            $sources[$id] ??= [
                'id' => $id, 'name' => $row->name_zh_cn ?: ($row->name_en_us ?: $row->aegis_name),
                'kind' => $row->kind, 'level' => (int) $row->level, 'drops' => [],
            ];
            $key = $row->method.':'.$row->rate;
            $sources[$id]['drops'][$key] ??= ['method' => $row->method, 'rate' => (int) $row->rate, 'count' => 0];
            $sources[$id]['drops'][$key]['count']++;
        }

        return array_values(array_map(function (array $source): array {
            $source['drops'] = array_values($source['drops']);

            return $source;
        }, $sources));
    }
}
