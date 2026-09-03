<?php

namespace App\Contracts\GameData;

use App\Data\GameData\MonsterQuery;

interface MonsterRepository
{
    /** @return array{data: list<array<string, mixed>>, total: int} */
    public function search(MonsterQuery $query): array;

    /** @return array<string, mixed>|null */
    public function find(int $monsterId): ?array;
}
