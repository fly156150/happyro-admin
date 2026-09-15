<?php

namespace App\Contracts\GameData;

use App\Data\GameData\NpcCatalogSnapshot;

interface NpcCatalogRepository
{
    /** @return array{version: string, imported: int, deleted: int} */
    public function replace(NpcCatalogSnapshot $snapshot): array;
}
