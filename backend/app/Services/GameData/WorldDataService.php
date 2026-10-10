<?php

namespace App\Services\GameData;

use App\Data\GameData\MapQuery;
use Collator;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

final class WorldDataService
{
    private const CACHE_TTL = 86400;

    /** Bump when the cached row shape or ordering changes. */
    private const CACHE_VERSION = 1;

    private ?Collator $collator = null;

    public function __construct(private readonly CacheRepository $cache) {}

    public function mapName(string $map): ?string
    {
        $entries = array_column($this->json($this->path('map-catalog.json'))['entries'] ?? [], null, 'map');

        return $entries[$map]['name'] ?? $this->json($this->path('map-names.zh-CN.json'))[$map] ?? null;
    }

    /**
     * Search the map catalog. Paginated because this feeds a browsable table.
     *
     * @return array{data: list<array<string, mixed>>, total: int}
     */
    public function maps(MapQuery $query): array
    {
        $canonicalMaps = $query->channelsEnabled ? [] : array_column(
            $this->json($this->path('map-catalog.json'))['entries'] ?? [], 'canonical_map', 'map',
        );
        $resolveMap = static function (?string $map) use ($canonicalMaps): ?string {
            if ($map === null) {
                return null;
            }
            $map = mb_strtolower($map);

            return $canonicalMaps[$map] ?? $map;
        };
        $onMap = $resolveMap($query->onMap);
        $currentMap = $resolveMap($query->currentMap);
        $rows = array_values(array_filter(
            $this->catalog($query->channelsEnabled),
            fn (array $row): bool => $this->visible($row, $query) && $this->matches($row, $query, $onMap),
        ));
        if ($query->adventureOrder) {
            $term = mb_strtolower(trim((string) $query->query));
            $order = $this->commonOrder();
            usort($rows, fn (array $left, array $right): int => $this->compareAdventure($left, $right, $term, $order));
        }
        if ($currentMap && ! $query->query) {
            $rows = $this->prioritize($rows, $currentMap);
        }

        return [
            'data' => array_slice($rows, ($query->page - 1) * $query->perPage, $query->perPage),
            'total' => count($rows),
        ];
    }

    /**
     * Resolve the on-disk preview for a map, honouring terrain and shared image aliases.
     */
    public function mapImagePath(string $map): ?string
    {
        $entry = array_column($this->json($this->path('map-catalog.json'))['entries'] ?? [], null, 'map')[$map] ?? null;
        $folder = ($entry['image_kind'] ?? null) === 'terrain' ? 'terrain' : 'maps';
        $path = $this->path($folder.'/'.($entry['image_map'] ?? $map).'.png');

        return is_file($path) ? $path : null;
    }

    /**
     * Merge and sort the server map index once per source revision, so requests
     * only filter and slice an already ordered list.
     *
     * @return list<array<string, mixed>>
     */
    private function catalog(bool $channelsEnabled): array
    {
        $indexPath = (string) config('happyro.game_data.map_index_path');
        $catalogPath = $this->path('map-catalog.json');
        $namesPath = $this->path('map-names.zh-CN.json');
        $imageRoot = $this->path('maps');
        $key = 'game-data:world:maps:'.md5(implode('|', [
            self::CACHE_VERSION,
            $indexPath, $this->revision($indexPath), $this->revision($catalogPath),
            $this->revision($namesPath), $this->revision($imageRoot),
            $channelsEnabled ? 'channels' : 'shared',
        ]));

        return $this->cache->remember($key, self::CACHE_TTL, fn (): array => $this->build(
            $indexPath, $catalogPath, $namesPath, $imageRoot, $channelsEnabled,
        ));
    }

    /** @return list<array<string, mixed>> */
    private function build(
        string $indexPath,
        string $catalogPath,
        string $namesPath,
        string $imageRoot,
        bool $channelsEnabled,
    ): array {
        $catalog = $this->json($catalogPath);
        $shared = array_column($catalog['entries'] ?? [], null, 'map');
        $names = $this->json($namesPath);
        $images = $this->imageNames($imageRoot);
        $version = (string) config('happyro.game_data.world_asset_version');

        $rows = [];
        $lastId = 0;
        foreach ($this->indexLines($indexPath) as [$map, $id]) {
            $lastId = $id ?? $lastId + 1;
            $entry = $shared[$map] ?? null;
            $name = $entry['name'] ?? ($names[$map] ?? null);
            $channel = $entry['channel'] ?? null;
            $imageKind = $entry['image_kind'] ?? null;
            $rows[] = [
                'id' => $lastId,
                'map' => $map,
                'name_zh_cn' => $channelsEnabled && $channel !== null ? $name.' · 频道 '.$channel : $name,
                'supported' => $entry['supported'] ?? false,
                'channel' => $channel,
                'image_kind' => $imageKind,
                'image' => match (true) {
                    $imageKind !== null => '/api/game-data/maps/'.$map.'/image?v='.($catalog['version'] ?? $version),
                    isset($images[$map]) => "/api/game-data/maps/{$map}/image?v={$version}",
                    default => null,
                },
            ];
        }
        usort($rows, $this->compare(...));

        return $rows;
    }

    /** @return list<array{0: string, 1: int|null}> */
    private function indexLines(string $path): array
    {
        if (! is_readable($path)) {
            return [];
        }
        $lines = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '//')) {
                continue;
            }
            [$name, $id] = array_pad(preg_split('/\s+/', $line), 2, null);
            if (is_string($name) && preg_match('/^[a-z0-9_@-]+$/', $name) === 1) {
                $lines[] = [$name, $id !== null ? (int) $id : null];
            }
        }

        return $lines;
    }

    /** @param array<string, mixed> $row */
    private function visible(array $row, MapQuery $query): bool
    {
        if (! $query->gameOnly) {
            return true;
        }

        return $row['supported']
            && ($query->channelsEnabled || $row['channel'] === null || $row['channel'] === 1);
    }

    /** @param array<string, mixed> $row */
    private function matches(array $row, MapQuery $query, ?string $onMap): bool
    {
        $contains = static fn (?string $haystack, ?string $needle): bool => $needle === null || $needle === ''
            || ($haystack !== null && str_contains(mb_strtolower($haystack), mb_strtolower($needle)));
        $search = $query->query === null || $query->query === ''
            || $contains($row['map'], $query->query) || $contains($row['name_zh_cn'], $query->query);
        $scoped = $onMap === null || $onMap === ''
            || $row['map'] === $onMap;

        return $search && $scoped && $contains($row['map'], $query->map) && $contains($row['name_zh_cn'], $query->name);
    }

    /**
     * Well known towns first, then localized name, mirroring the in-game catalog order.
     *
     * @param  array<string, mixed>  $left
     * @param  array<string, mixed>  $right
     */
    private function compare(array $left, array $right): int
    {
        $order = $this->commonOrder();
        $rank = static fn (string $map): int => $order[$map] ?? count($order);

        return $rank($left['map']) <=> $rank($right['map'])
            ?: $this->collator()->compare(
                (string) ($left['name_zh_cn'] ?? $left['map']),
                (string) ($right['name_zh_cn'] ?? $right['map']),
            )
            ?: strcmp($left['map'], $right['map']);
    }

    /**
     * Preserve the adventure catalog ranking from happyro-client MapCatalogData.js.
     * Admin tables retain their existing town/name ordering.
     *
     * @param  array<string, mixed>  $left
     * @param  array<string, mixed>  $right
     */
    private function compareAdventure(array $left, array $right, string $term, array $order): int
    {
        $rank = function (array $row) use ($term, $order): array {
            $values = [mb_strtolower($row['map']), mb_strtolower((string) $row['name_zh_cn'])];
            $match = $term === '' || in_array($term, $values, true) ? 0
                : (array_any($values, static fn (string $value): bool => str_starts_with($value, $term)) ? 1 : 2);
            $map = $row['map'];
            $kind = match (true) {
                isset($order[$map]) => 1,
                preg_match('/^(?:pvp_|guild_vs|bat_|schg_|teg_|gvg_)/', $map) === 1,
                preg_match('/\\bpvp\\b|对战|竞技场/ui', (string) $row['name_zh_cn']) === 1 => 5,
                preg_match('/^(?:[12]@|e_|dali|ver_eju|glast_01)/', $map) === 1 => 4,
                preg_match('/(?:_in\\d*|_dun\\d*|dun\\d*|_q\\d*|_room|_boss)$/', $map) === 1 => 3,
                default => 2,
            };

            return [$match, $row['image_kind'] === 'image' ? 0 : 1, $kind, $order[$map] ?? 0];
        };

        return $rank($left) <=> $rank($right)
            ?: $this->collator()->compare((string) $left['name_zh_cn'], (string) $right['name_zh_cn'])
            ?: strcmp($left['map'], $right['map']);
    }

    private function collator(): Collator
    {
        return $this->collator ??= new Collator('zh-CN');
    }

    /**
     * Move the player's current map to the front of an already sorted list,
     * keeping every other row in its existing relative order.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function prioritize(array $rows, string $map): array
    {
        foreach ($rows as $index => $row) {
            if ($row['map'] === $map) {
                $current = $rows[$index];
                unset($rows[$index]);
                array_unshift($rows, $current);
                break;
            }
        }

        return array_values($rows);
    }

    /** @return array<string, int> */
    private function commonOrder(): array
    {
        $common = $this->json($this->path('map-order.json'))['common'] ?? [];

        return array_flip(array_values($common));
    }

    /** @return array<string, true> */
    private function imageNames(string $root): array
    {
        $names = [];
        foreach (glob($root.'/*.png') ?: [] as $file) {
            $names[basename($file, '.png')] = true;
        }

        return $names;
    }

    /** @return array<string, mixed> */
    private function json(string $path): array
    {
        $contents = is_readable($path) ? file_get_contents($path) : false;
        $decoded = $contents === false ? null : json_decode($contents, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function path(string $name): string
    {
        return base_path("resources/game-data/world/{$name}");
    }

    private function revision(string $path): string
    {
        return (string) (@filemtime($path) ?: 0);
    }
}
