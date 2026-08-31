<?php

namespace App\Services\Audit;

use App\Contracts\Audit\AuditWriter;
use App\Data\Auth\ClientContext;
use App\Models\AuditLog;
use App\Models\User;

final class EloquentAuditWriter implements AuditWriter
{
    public function write(
        string $event,
        ClientContext $context,
        ?User $user = null,
        ?string $username = null,
        array $metadata = [],
    ): void {
        AuditLog::query()->create([
            'user_id' => $user?->getKey(),
            'username' => $username,
            'event' => $event,
            'ip_address' => $context->ipAddress,
            'user_agent' => $context->userAgent,
            'metadata' => $metadata ?: null,
        ]);
    }
}
