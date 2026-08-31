<?php

namespace App\Contracts\Players;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LoginLogRepository
{
    public function paginate(?string $username, int $perPage): LengthAwarePaginator;
}
