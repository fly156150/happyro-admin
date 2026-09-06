<?php

namespace App\Infrastructure\Persistence\Audit;

use App\Contracts\Audit\AuditLogRepository;
use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentAuditLogRepository implements AuditLogRepository
{
    public function paginateEvent(string $event, int $perPage): LengthAwarePaginator
    {
        return AuditLog::query()
            ->with('user:id,name,username')
            ->where('event', $event)
            ->latest()
            ->paginate($perPage);
    }
}
