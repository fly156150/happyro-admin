<?php

namespace App\Services\GameServer;

use App\Contracts\GameServer\GameServerCommandRepository;
use App\Data\GameServer\GameServerCommandRequest;
use App\Data\GameServer\GameServerCommandSubmission;
use App\Data\GameServer\OperationActor;
use App\Models\User;

final class SubmitGameServerCommandService
{
    public function __construct(private readonly GameServerCommandRepository $commands) {}

    public function submit(GameServerCommandRequest $request, User $operator): GameServerCommandSubmission
    {
        return $this->submitForActor($request, OperationActor::admin($operator));
    }

    public function submitForGameAccount(GameServerCommandRequest $request, int $accountId): GameServerCommandSubmission
    {
        return $this->submitForActor($request, OperationActor::gameAccount($accountId));
    }

    public function submitForActor(GameServerCommandRequest $request, OperationActor $actor): GameServerCommandSubmission
    {
        return $this->commands->submit($request, $actor);
    }
}
