<?php

namespace App\Services\GameData;

final class WorldDataService
{
    private const WORLD_ASSET_VERSION = 'kro-20211105-transparent-v2';

    private ?array $mapNames = null;

    /** @return list<array{id:int|null,map:string}> */
    public function maps(): array
    {
        $path = config('happyro.game_data.map_index_path');
        $rows = [];
        $lastId = 0;
        if (is_string($path) && is_readable($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '//')) {
                    continue;
                }
                [$name, $id] = array_pad(preg_split('/\s+/', $line), 2, null);
                if (is_string($name) && preg_match('/^[a-z0-9_]+$/', $name) === 1) {
                    $lastId = $id !== null ? (int) $id : $lastId + 1;
                    $rows[] = [
                        'id' => $lastId,
                        'map' => $name,
                        'name_zh_cn' => $this->mapNames()[$name] ?? null,
                        'image' => is_file(base_path("resources/game-data/world/maps/{$name}.png")) ? "/api/game-data/maps/{$name}/image?v=".self::WORLD_ASSET_VERSION : null,
                    ];
                }
            }
        }

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    public function npcs(): array
    {
        $path = config('happyro.game_data.npc_catalog_path');
        if (! is_string($path) || ! is_readable($path)) {
            return [];
        }

        $contents = file_get_contents($path);
        $catalog = $contents === false ? null : json_decode($contents, true);
        if (! is_array($catalog) || ($catalog['schema'] ?? null) !== 'happyro-npc-catalog/v1' || ! is_array($catalog['entries'] ?? null)) {
            return [];
        }

        $rows = array_map(function (array $entry): array {
            $spriteId = $entry['display_sprite_id'] ?? null;

            return [
                ...$entry,
                'name_zh_cn' => $entry['display_name'] !== $entry['source_name'] ? $entry['display_name'] : null,
                'image' => is_int($spriteId) && is_file(base_path("resources/game-data/world/npcs/{$spriteId}.png"))
                    ? "/api/game-data/npcs/{$spriteId}/image?v=".self::WORLD_ASSET_VERSION
                : null,
            ];
        }, $catalog['entries']);
        usort($rows, static fn (array $left, array $right): int => $left['catalog_order'] <=> $right['catalog_order']);

        return $rows;
    }

    /** @return array<string, string> */
    private function mapNames(): array
    {
        return $this->mapNames ??= $this->names('map-names.zh-CN.json');
    }

    /** @return array<string, string> */
    private function names(string $filename): array
    {
        $contents = file_get_contents(base_path("resources/game-data/world/{$filename}"));
        $names = $contents === false ? null : json_decode($contents, true);

        return is_array($names) ? $names : [];
    }
}
