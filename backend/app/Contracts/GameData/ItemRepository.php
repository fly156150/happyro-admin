<?php

namespace App\Contracts\GameData;

use App\Data\GameData\ItemQuery;

interface ItemRepository
{
    /** @return array{data: list<array<string, mixed>>, total: int} */
    public function search(ItemQuery $query): array;

    /** @return array<string, mixed>|null */
    public function find(int $itemId, string $range): ?array;
}
