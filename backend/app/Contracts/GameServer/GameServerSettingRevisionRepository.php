<?php

namespace App\Contracts\GameServer;

use App\Models\GameServerSettingRevision;

interface GameServerSettingRevisionRepository
{
    /** @param array<string, mixed> $changes */
    public function create(string $serverKey, array $changes, string $reason, ?int $requestedBy): GameServerSettingRevision;

    public function find(string $serverKey, int $revision): ?GameServerSettingRevision;

    public function markApplied(int $id): GameServerSettingRevision;

    public function markFailed(int $id): GameServerSettingRevision;
}
