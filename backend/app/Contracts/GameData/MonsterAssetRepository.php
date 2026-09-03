<?php

namespace App\Contracts\GameData;

interface MonsterAssetRepository
{
    public function imagePath(int $monsterId): ?string;
}
