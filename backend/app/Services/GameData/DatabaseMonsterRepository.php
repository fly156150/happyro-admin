<?php

namespace App\Services\GameData;

use App\Contracts\GameData\GameDataSettingRepository;
use App\Contracts\GameData\MonsterRepository;
use App\Data\GameData\MonsterQuery;
use App\Models\GameDataCatalog;
use App\Models\GameItemView;
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
            ->when($query->kind, fn (Builder $rows, string $value): Builder => $this->classified($rows, $value));
        $total = (clone $builder)->count();
        $data = $builder->orderBy('monster_id')->forPage($query->page, $query->perPage)->get();

        return ['data' => $data->map($this->payload(...))->all(), 'total' => $total];
    }

    public function find(int $monsterId): ?array
    {
        $monster = $this->query()->where('monster_id', $monsterId)->first();

        return $monster ? $this->withLocalizedDrops($this->payload($monster)) : null;
    }

    private function query(): Builder
    {
        $version = $this->settings->current()->server;
        $catalogId = GameDataCatalog::query()->where('resource_type', 'monsters')
            ->where('source', 'server')->where('ruleset', 'renewal')
            ->where('source_version', $version)->value('id');

        return GameMonster::query()->with('catalog:id,source_version')->where('game_data_catalog_id', $catalogId ?? 0);
    }

    private function classified(Builder $builder, string $kind): Builder
    {
        return $kind === 'all' ? $builder : $builder->where('kind', $kind);
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
            'kind' => $monster->kind, 'serverVersion' => $monster->catalog->source_version,
        ];
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    private function withLocalizedDrops(array $payload): array
    {
        $lists = [];
        foreach (['Drops', 'MvpDrops'] as $field) {
            if (array_key_exists($field, $payload) && is_array($payload[$field])) {
                $lists[$field] = $payload[$field];
            }
        }
        if ($lists === []) {
            return $payload;
        }

        $aegisNames = [];
        foreach ($lists as $drops) {
            foreach ($drops as $drop) {
                if (is_array($drop) && is_string($drop['Item'] ?? null) && $drop['Item'] !== '') {
                    $aegisNames[$drop['Item']] = true;
                }
            }
        }
        $items = $this->itemsByAegisName(array_keys($aegisNames));
        foreach ($lists as $field => $drops) {
            $payload[$field] = array_map(fn (mixed $drop): mixed => $this->localizedDrop($drop, $items), $drops);
        }

        return $payload;
    }

    /**
     * @param  array<string, array{itemId: int, names: array{zh-CN: string, en-US: string}}>  $items
     */
    private function localizedDrop(mixed $drop, array $items): mixed
    {
        if (! is_array($drop)) {
            return $drop;
        }

        $aegis = (string) ($drop['Item'] ?? '');
        $item = $items[$aegis] ?? null;
        $drop['itemId'] = $item['itemId'] ?? 0;
        $drop['names'] = $item['names'] ?? ['zh-CN' => $aegis, 'en-US' => $aegis];

        return $drop;
    }

    /**
     * @param  list<string>  $aegisNames
     * @return array<string, array{itemId: int, names: array{zh-CN: string, en-US: string}}>
     */
    private function itemsByAegisName(array $aegisNames): array
    {
        if ($aegisNames === []) {
            return [];
        }

        $versions = $this->settings->current();
        $clientCatalogId = $this->itemCatalogId('client', 'client', $versions->client);
        $serverCatalogId = $this->itemCatalogId('server', 'renewal', $versions->server);

        return GameItemView::query()
            ->where('client_catalog_id', $clientCatalogId)
            ->where('server_catalog_id', $serverCatalogId)
            ->whereIn('aegis_name', $aegisNames)
            ->get(['aegis_name', 'item_id', 'name_zh_cn', 'name_en_us'])
            ->mapWithKeys(fn (GameItemView $view): array => [
                (string) $view->aegis_name => [
                    'itemId' => (int) $view->item_id,
                    'names' => [
                        'zh-CN' => $view->name_zh_cn !== '' ? $view->name_zh_cn : (string) $view->aegis_name,
                        'en-US' => $view->name_en_us !== '' ? $view->name_en_us : (string) $view->aegis_name,
                    ],
                ],
            ])
            ->all();
    }

    private function itemCatalogId(string $source, string $ruleset, string $version): int
    {
        return (int) (GameDataCatalog::query()
            ->where('resource_type', 'items')
            ->where('source', $source)
            ->where('ruleset', $ruleset)
            ->where('source_version', $version)
            ->value('id') ?? 0);
    }
}
