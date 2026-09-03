<?php

namespace App\Services\GameData;

use App\Contracts\GameData\MonsterSnapshotReader;
use App\Data\GameData\MonsterCatalogSnapshot;
use JsonException;
use RuntimeException;

final class JsonMonsterSnapshotReader implements MonsterSnapshotReader
{
    public function read(string $path): MonsterCatalogSnapshot
    {
        if (! is_file($path)) {
            throw new RuntimeException("Monster snapshot not found: {$path}");
        }
        try {
            $payload = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Invalid monster snapshot: {$path}", previous: $exception);
        }
        if (! is_array($payload) || ($payload['schema'] ?? null) !== 'monster-catalog/v1'
            || ! is_array($payload['monsters'] ?? null) || $payload['monsters'] === []) {
            throw new RuntimeException("Unsupported monster snapshot: {$path}");
        }
        $version = $payload['serverSource']['revision'] ?? null;
        $hash = hash_file('sha256', $path);
        if (! is_string($version) || $version === '' || ! is_string($hash)) {
            throw new RuntimeException("Monster snapshot metadata is incomplete: {$path}");
        }

        return new MonsterCatalogSnapshot(
            $version, $hash, array_diff_key($payload, ['monsters' => true]), $payload['monsters'],
        );
    }
}
