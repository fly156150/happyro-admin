<?php

namespace App\Services\GameData;

use App\Contracts\GameData\GameDataSettingRepository;
use App\Contracts\GameData\MonsterRepository;
use App\Data\GameData\MonsterQuery;
use App\Models\GameDataCatalog;
use App\Models\GameMonster;
use Illuminate\Database\Eloquent\Builder;

final class DatabaseMonsterRepository implements MonsterRepository
{
    public function __construct(private readonly GameDataSettingRepository $settings) {}

    public function search(MonsterQuery $query): array
    {
        $builder = $this->query()
            ->when($query->query, fn (Builder $rows, string $value): Builder => $this->matching($rows, $value))
            ->when($query->race, fn (Builder $rows, string $value): Builder => $rows->where('race', $value))
            ->when($query->element, fn (Builder $rows, string $value): Builder => $rows->where('element', $value))
            ->when($query->size, fn (Builder $rows, string $value): Builder => $rows->where('size', $value))
            ->when($query->boss !== null, fn (Builder $rows): Builder => $rows->where('is_boss', $query->boss));
        $total = (clone $builder)->count();
        $data = $builder->orderBy('monster_id')->forPage($query->page, $query->perPage)->get();

        return ['data' => $data->map($this->payload(...))->all(), 'total' => $total];
    }

    public function find(int $monsterId): ?array
    {
        $monster = $this->query()->where('monster_id', $monsterId)->first();

        return $monster ? $this->payload($monster) : null;
    }

    private function query(): Builder
    {
        $version = $this->settings->current()->server;
        $catalogId = GameDataCatalog::query()->where('resource_type', 'monsters')
            ->where('source', 'server')->where('ruleset', 'renewal')
            ->where('source_version', $version)->value('id');

        return GameMonster::query()->with('catalog:id,source_version')->where('game_data_catalog_id', $catalogId ?? 0);
    }

    private function matching(Builder $builder, string $value): Builder
    {
        $escaped = addcslashes($value, '%_\\');

        return $builder->where(function (Builder $match) use ($escaped, $value): void {
            $match->where('name_zh_cn', 'like', "%{$escaped}%")
                ->orWhere('name_en_us', 'like', "%{$escaped}%")
                ->orWhere('aegis_name', 'like', "%{$escaped}%");
            if (ctype_digit($value)) {
                $match->orWhere('monster_id', (int) $value);
            }
        });
    }

    /** @return array<string, mixed> */
    private function payload(GameMonster $monster): array
    {
        return [
            'Id' => $monster->monster_id, ...$monster->payload,
            'AegisName' => $monster->aegis_name,
            'names' => ['zh-CN' => $monster->name_zh_cn, 'en-US' => $monster->name_en_us],
            'Level' => $monster->level, 'Hp' => $monster->hp,
            'Size' => $monster->size, 'Race' => $monster->race,
            'Element' => $monster->element, 'ElementLevel' => $monster->element_level,
            'isBoss' => $monster->is_boss, 'serverVersion' => $monster->catalog->source_version,
        ];
    }
}
