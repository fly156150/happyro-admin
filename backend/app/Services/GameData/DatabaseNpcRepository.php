<?php

namespace App\Services\GameData;

use App\Contracts\GameData\NpcRepository;
use App\Data\GameData\NpcQuery;
use App\Models\GameDataCatalog;
use App\Models\GameNpc;
use Illuminate\Database\Eloquent\Builder;

final class DatabaseNpcRepository implements NpcRepository
{
    /** @var list<string> Columns the frontend ranks a search term against. */
    private const SEARCHABLE = ['display_name', 'source_name', 'name', 'map_name_zh_cn', 'map', 'payload->navigation->name', 'payload->navigation->class'];

    public function search(NpcQuery $query): array
    {
        $builder = $this->query()
            ->when($query->gameVisibleOnly, fn (Builder $rows): Builder => $rows->where('game_visible', true))
            ->when($query->onMap, fn (Builder $rows, string $value): Builder => $rows->where('map', $this->normalizeMap($value)))
            ->when($query->map, fn (Builder $rows, string $value): Builder => $rows->where(
                fn (Builder $match): Builder => $match
                    ->where('map', 'like', '%'.$this->escape($value).'%')
                    ->orWhere('map_name_zh_cn', 'like', '%'.$this->escape($value).'%'),
            ))
            ->when($query->name, fn (Builder $rows, string $value): Builder => $rows
                ->where('name', 'like', '%'.$this->escape($value).'%'))
            ->when($query->displayName, fn (Builder $rows, string $value): Builder => $rows
                ->where('display_name', 'like', '%'.$this->escape($value).'%'))
            ->when($query->query, fn (Builder $rows, string $value): Builder => $this->matching($rows, $value));
        $total = (clone $builder)->count();
        $records = $this->ordered($builder, $query)->forPage($query->page, $query->perPage)->get();

        return ['data' => $records->map($this->payload(...))->all(), 'total' => $total];
    }

    public function onMap(string $map, bool $gameVisibleOnly): array
    {
        $records = $this->query()
            ->where('map', $this->normalizeMap($map))
            ->when($gameVisibleOnly, fn (Builder $rows): Builder => $rows->where('game_visible', true))
            ->orderBy('display_name')->orderBy('x')->orderBy('y')
            ->get();

        return $records->map($this->payload(...))->all();
    }

    /**
     * The NPC catalog carries its own generator version, so the newest import wins
     * instead of being pinned by the client/server version setting.
     */
    private function query(): Builder
    {
        $catalogId = GameDataCatalog::query()->where('resource_type', 'npcs')
            ->orderByDesc('imported_at')->value('id');

        return GameNpc::query()->where('game_data_catalog_id', $catalogId ?? 0);
    }

    /**
     * Mirror the admin table ranking: exact match first, then prefix, then substring.
     */
    private function ordered(Builder $builder, NpcQuery $query): Builder
    {
        $term = mb_strtolower(trim((string) ($query->displayName ?: $query->name ?: $query->map ?: $query->query)));
        if ($query->currentMap && ($term !== '' || $query->onMap)) {
            $builder->orderByRaw('CASE WHEN map = ? THEN 0 ELSE 1 END', [$this->normalizeMap($query->currentMap)]);
        }
        if ($term === '') {
            return $builder->orderBy('catalog_order');
        }
        $escaped = $this->escape($term);
        $columns = array_map($builder->getQuery()->getGrammar()->wrap(...), self::SEARCHABLE);
        $clauses = [];
        $bindings = [];
        $aliases = $this->searchAliases();
        $navigationName = $builder->getQuery()->getGrammar()->wrap('payload->navigation->name');
        foreach ([$term, $escaped.'%', '%'.$escaped.'%'] as $rank => $pattern) {
            $operator = $rank === 0 ? '=' : 'LIKE';
            $matches = array_map(static fn (string $column): string => "LOWER({$column}) {$operator} ?", $columns);
            array_push($bindings, ...array_fill(0, count($columns), $pattern));
            foreach ($aliases as $alias => $source) {
                $alias = mb_strtolower($alias);
                $aliasRank = $alias === $term ? 0 : (str_starts_with($alias, $term) ? 1 : 2);
                if ($aliasRank === $rank && str_contains($alias, $term)) {
                    $matches[] = "LOWER({$navigationName}) LIKE ?";
                    $bindings[] = '%'.$this->escape(mb_strtolower($source)).'%';
                }
            }
            $clauses[] = 'WHEN '.implode(' OR ', $matches)." THEN {$rank}";
        }

        return $builder
            ->orderByRaw('CASE '.implode(' ', $clauses).' ELSE 3 END', $bindings)
            ->orderBy('display_name')
            ->orderBy('catalog_order');
    }

    private function matching(Builder $builder, string $value): Builder
    {
        $escaped = $this->escape($value);

        return $builder->where(function (Builder $match) use ($escaped, $value): void {
            foreach (self::SEARCHABLE as $index => $column) {
                $index === 0
                    ? $match->where($column, 'like', "%{$escaped}%")
                    : $match->orWhere($column, 'like', "%{$escaped}%");
            }
            foreach ($this->searchAliases() as $alias => $source) {
                if (str_contains(mb_strtolower($alias), mb_strtolower($value))) {
                    $match->orWhere('payload->navigation->name', 'like', '%'.$this->escape($source).'%');
                }
            }
        });
    }

    /** @return array<string, string> */
    private function searchAliases(): array
    {
        return json_decode((string) file_get_contents(resource_path('game-data/world/npc-search-aliases.json')), true)['aliases'];
    }

    private function escape(string $value): string
    {
        return addcslashes($value, '%_\\');
    }

    private function normalizeMap(string $map): string
    {
        return mb_strtolower((string) preg_replace('/\.gat$/i', '', trim($map)));
    }

    /** @return array<string, mixed> */
    private function payload(GameNpc $npc): array
    {
        return [
            'id' => $npc->npc_key,
            'map' => $npc->map,
            'map_name_zh_cn' => $npc->map_name_zh_cn,
            'x' => $npc->x,
            'y' => $npc->y,
            'name' => $npc->name,
            'source_name' => $npc->source_name,
            'display_name' => $npc->display_name,
            'name_zh_cn' => $npc->display_name !== $npc->source_name ? $npc->display_name : null,
            'type' => $npc->type,
            'sprite_id' => $npc->sprite_id,
            'display_sprite_id' => $npc->display_sprite_id,
            'image_available' => $npc->image_available,
            'enabled' => $npc->enabled,
            'game_visible' => $npc->game_visible,
            'catalog_order' => $npc->catalog_order,
            ...$npc->payload,
        ];
    }
}
