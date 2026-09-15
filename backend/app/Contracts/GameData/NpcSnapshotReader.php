<?php

namespace App\Contracts\GameData;

use App\Data\GameData\NpcCatalogSnapshot;

interface NpcSnapshotReader
{
    public function read(string $path): NpcCatalogSnapshot;
}
