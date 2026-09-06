<?php

namespace App\Contracts\Audit;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AuditLogRepository
{
    /** @return LengthAwarePaginator<int, AuditLog> */
    public function paginateEvent(string $event, int $perPage): LengthAwarePaginator;
}
