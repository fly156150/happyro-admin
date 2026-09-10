<?php

namespace App\Services\GameData;

use App\Contracts\GameData\ItemAssetRepository;

final class LocalItemAssetRepository implements ItemAssetRepository
{
    public function __construct(private readonly string $imageRoot) {}

    public function iconPath(int $itemId): ?string
    {
        return $this->resourcePath($itemId, 'icons');
    }

    public function illustrationPath(int $itemId): ?string
    {
        return $this->resourcePath($itemId, 'illustrations');
    }

    private function resourcePath(int $itemId, string $directory): ?string
    {
        $root = realpath($this->imageRoot);
        $path = realpath($this->imageRoot.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$itemId.'.png');

        return $path && $root && str_starts_with($path, $root.DIRECTORY_SEPARATOR) && is_file($path) ? $path : null;
    }
}
