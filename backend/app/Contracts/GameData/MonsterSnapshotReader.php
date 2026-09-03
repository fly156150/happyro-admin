<?php

namespace App\Contracts\GameData;

use App\Data\GameData\MonsterCatalogSnapshot;

interface MonsterSnapshotReader
{
    public function read(string $path): MonsterCatalogSnapshot;
}
