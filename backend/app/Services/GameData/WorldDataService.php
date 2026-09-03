<?php

namespace App\Services\GameData;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class WorldDataService
{
    private const WORLD_ASSET_VERSION = 'kro-20211105-transparent-v2';

    private ?array $mapNames = null;

    private ?array $npcNames = null;

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

    /** @return list<array{map:string,x:int,y:int,name:string}> */
    public function npcs(): array
    {
        $root = config('happyro.game_data.npc_root');
        $rows = [];
        if (! is_string($root) || ! is_dir($root)) {
            return $rows;
        }
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($files as $entry) {
            if (! $entry->isFile() || strtolower($entry->getExtension()) !== 'txt') {
                continue;
            }
            foreach (file($entry->getPathname(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                if (preg_match('/^([a-z0-9_]+),(-?\d+),(-?\d+),\d+\s+(?:script|shop|cashshop|itemshop|pointshop)\s+(.+?)\s+(\d+),?\s*\{/i', trim($line), $match) === 1) {
                    $spriteId = (int) $match[5];
                    $name = trim($match[4]);
                    $lookupName = preg_replace('/#.*$/', '', $name) ?? $name;
                    $rows[] = [
                        'map' => $match[1],
                        'map_name_zh_cn' => $this->mapNames()[$match[1]] ?? null,
                        'x' => (int) $match[2],
                        'y' => (int) $match[3],
                        'name' => $name,
                        'name_zh_cn' => $this->npcNames()[$lookupName] ?? null,
                        'sprite_id' => $spriteId,
                        'image' => is_file(base_path("resources/game-data/world/npcs/{$spriteId}.png")) ? "/api/game-data/npcs/{$spriteId}/image?v=".self::WORLD_ASSET_VERSION : null,
                    ];
                }
            }
        }

        return $rows;
    }

    /** @return array<string, string> */
    private function mapNames(): array
    {
        return $this->mapNames ??= $this->names('map-names.zh-CN.json');
    }

    /** @return array<string, string> */
    private function npcNames(): array
    {
        return $this->npcNames ??= $this->names('npc-names.zh-CN.json');
    }

    /** @return array<string, string> */
    private function names(string $filename): array
    {
        $contents = file_get_contents(base_path("resources/game-data/world/{$filename}"));
        $names = $contents === false ? null : json_decode($contents, true);

        return is_array($names) ? $names : [];
    }
}
