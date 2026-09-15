<?php

namespace App\Services\GameData;

use App\Contracts\GameData\NpcSnapshotReader;
use App\Data\GameData\NpcCatalogSnapshot;
use JsonException;
use RuntimeException;

final class JsonNpcSnapshotReader implements NpcSnapshotReader
{
    public function read(string $path): NpcCatalogSnapshot
    {
        if (! is_file($path)) {
            throw new RuntimeException("NPC catalog not found: {$path}");
        }
        try {
            $payload = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Invalid NPC catalog: {$path}", previous: $exception);
        }
        if (! is_array($payload) || ($payload['schema'] ?? null) !== 'happyro-npc-catalog/v1'
            || ! is_array($payload['entries'] ?? null) || $payload['entries'] === []) {
            throw new RuntimeException("Unsupported NPC catalog: {$path}");
        }
        $version = $payload['version'] ?? null;
        $hash = $payload['content_sha256'] ?? hash_file('sha256', $path);
        if (! is_string($version) || $version === '' || ! is_string($hash)) {
            throw new RuntimeException("NPC catalog metadata is incomplete: {$path}");
        }

        return new NpcCatalogSnapshot(
            $version, $hash, array_diff_key($payload, ['entries' => true]), array_values($payload['entries']),
        );
    }
}
