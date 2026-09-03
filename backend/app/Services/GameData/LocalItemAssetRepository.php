<?php

namespace App\Services\GameData;

use App\Contracts\GameData\ItemAssetRepository;
use RuntimeException;

final class LocalItemAssetRepository implements ItemAssetRepository
{
    private ?array $assetMap = null;

    public function __construct(
        private readonly string $assetMapPath,
        private readonly string $resourceRoot,
    ) {}

    public function iconPath(int $itemId): ?string
    {
        return $this->resourcePath($itemId, 'icon');
    }

    public function illustrationPath(int $itemId): ?string
    {
        return $this->resourcePath($itemId, 'illustration');
    }

    private function resourcePath(int $itemId, string $assetType): ?string
    {
        $relativePath = $this->assetMap()[(string) $itemId][$assetType] ?? null;
        if (! is_string($relativePath) || $relativePath === '') {
            return null;
        }
        $root = realpath($this->resourceRoot);
        $path = realpath($this->resourceRoot.DIRECTORY_SEPARATOR.$relativePath);

        return $path && $root && str_starts_with($path, $root.DIRECTORY_SEPARATOR) && is_file($path) ? $path : null;
    }

    private function assetMap(): array
    {
        return $this->assetMap ??= $this->readItems($this->assetMapPath, 'Item asset map');
    }

    private function readItems(string $path, string $label): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("{$label} not found: {$path}");
        }
        $payload = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        return is_array($payload['items'] ?? null) ? $payload['items'] : [];
    }
}
