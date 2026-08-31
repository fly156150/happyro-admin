<?php

namespace App\Contracts\Audit;

use App\Data\Auth\ClientContext;
use App\Models\User;

interface AuditWriter
{
    /** @param array<string, mixed> $metadata */
    public function write(
        string $event,
        ClientContext $context,
        ?User $user = null,
        ?string $username = null,
        array $metadata = [],
    ): void;
}
