<?php

namespace App\Services\GameData;

use App\Contracts\GameData\ItemSnapshotReader;
use App\Data\GameData\ItemCatalogSnapshot;
use JsonException;
use RuntimeException;

final class JsonItemSnapshotReader implements ItemSnapshotReader
{
    public function read(string $path): ItemCatalogSnapshot
    {
        if (! is_file($path)) {
            throw new RuntimeException("Item snapshot not found: {$path}");
        }

        try {
            $payload = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Invalid item snapshot: {$path}", previous: $exception);
        }

        if (! is_array($payload) || ($payload['schema'] ?? null) !== 'item-catalog/v2' || ! is_array($payload['items'] ?? null)) {
            throw new RuntimeException("Unsupported item snapshot: {$path}");
        }
        if ($payload['items'] === []) {
            throw new RuntimeException("Item snapshot is empty: {$path}");
        }

        [$source, $ruleset, $version] = $this->identity($payload);
        $contentHash = hash_file('sha256', $path);
        if (! is_string($contentHash)) {
            throw new RuntimeException("Unable to hash item snapshot: {$path}");
        }

        return new ItemCatalogSnapshot(
            $source,
            $ruleset,
            $version,
            $contentHash,
            array_diff_key($payload, ['items' => true]),
            $payload['items'],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{string, string, string}
     */
    private function identity(array $payload): array
    {
        if (($payload['mode'] ?? null) === 'client-only') {
            return ['client', 'client', $this->requiredVersion($payload['clientSource']['version'] ?? null)];
        }

        $mode = $payload['mode'] ?? null;
        if (! in_array($mode, ['renewal', 'pre-renewal'], true)) {
            throw new RuntimeException('Unsupported item snapshot mode');
        }

        return ['server', $mode, $this->requiredVersion($payload['englishSource']['revision'] ?? null)];
    }

    private function requiredVersion(mixed $version): string
    {
        if (! is_string($version) || $version === '') {
            throw new RuntimeException('Item snapshot source version is missing');
        }

        return $version;
    }
}
