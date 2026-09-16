<?php

namespace App\Contracts\GameServer;

use App\Data\GameServer\OperationActor;
use App\Models\GameServerSettingRevision;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GameServerSettingRevisionRepository
{
    /** @param array<string, mixed> $changes */
    public function create(string $serverKey, array $changes, OperationActor $actor): GameServerSettingRevision;

    public function find(string $serverKey, int $revision): ?GameServerSettingRevision;

    public function markApplied(int $id): GameServerSettingRevision;

    public function markFailed(int $id): GameServerSettingRevision;

    /** @return LengthAwarePaginator<int, GameServerSettingRevision> */
    public function paginate(int $perPage): LengthAwarePaginator;
}
