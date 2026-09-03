<?php

namespace App\Contracts\GameData;

interface ItemAssetRepository
{
    public function iconPath(int $itemId): ?string;

    public function illustrationPath(int $itemId): ?string;
}
