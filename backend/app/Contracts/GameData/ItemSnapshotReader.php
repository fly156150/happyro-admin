<?php

namespace App\Contracts\GameData;

use App\Data\GameData\ItemCatalogSnapshot;

interface ItemSnapshotReader
{
    public function read(string $path): ItemCatalogSnapshot;
}
