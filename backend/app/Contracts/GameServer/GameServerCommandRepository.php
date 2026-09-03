<?php

namespace App\Contracts\GameServer;

use App\Data\GameServer\GameServerCommand;
use App\Data\GameServer\GameServerCommandRequest;
use App\Data\GameServer\GameServerCommandSubmission;

interface GameServerCommandRepository
{
    public function submit(GameServerCommandRequest $request, ?int $requestedBy): GameServerCommandSubmission;

    public function find(string $id): ?GameServerCommand;

    public function markRunning(string $id): GameServerCommand;

    /** @param array<string, mixed> $result */
    public function markSucceeded(string $id, array $result): GameServerCommand;

    public function markFailed(string $id, string $errorCode, string $errorMessage): GameServerCommand;

    public function markIndeterminate(string $id, string $errorCode, string $errorMessage): GameServerCommand;
}
