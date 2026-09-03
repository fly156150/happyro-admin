<?php

namespace App\Services\GameData;

use App\Contracts\GameData\MonsterAssetRepository;

final readonly class LocalMonsterAssetRepository implements MonsterAssetRepository
{
    public function __construct(private string $root) {}

    public function imagePath(int $monsterId): ?string
    {
        $path = rtrim($this->root, '/')."/{$monsterId}.png";

        return is_file($path) ? $path : null;
    }
}
