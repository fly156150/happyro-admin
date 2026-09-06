<?php

namespace App\Contracts\GameData;

use App\Data\GameData\MonsterCatalogSnapshot;

interface MonsterCatalogRepository
{
    /** @return array{version: string, imported: int, deleted: int} */
    public function replace(MonsterCatalogSnapshot $snapshot): array;
}
