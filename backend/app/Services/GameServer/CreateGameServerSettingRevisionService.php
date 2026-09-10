<?php

namespace App\Services\GameServer;

use App\Contracts\GameServer\GameServerSettingRevisionRepository;
use App\Models\GameServerSettingRevision;
use App\Models\User;

final readonly class CreateGameServerSettingRevisionService
{
    public function __construct(private GameServerSettingRevisionRepository $revisions) {}

    /** @param array<string, mixed> $changes */
    public function create(string $serverKey, array $changes, ?string $remark, User $operator): GameServerSettingRevision
    {
        return $this->revisions->create($serverKey, $changes, $remark, $operator->getKey());
    }
}
