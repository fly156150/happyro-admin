<?php

namespace App\Contracts\Players;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PlayerCharacterRepository
{
    public function paginate(?string $username, int $perPage): LengthAwarePaginator;

    public function find(int $charId): ?array;
}
