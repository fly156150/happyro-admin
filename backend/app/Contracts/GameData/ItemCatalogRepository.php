<?php

namespace App\Contracts\GameData;

use App\Data\GameData\ItemCatalogSnapshot;

interface ItemCatalogRepository
{
    /**
     * @param  list<ItemCatalogSnapshot>  $snapshots
     * @return list<array{source: string, ruleset: string, version: string, imported: int, deleted: int}>
     */
    public function replaceMany(array $snapshots): array;
}
