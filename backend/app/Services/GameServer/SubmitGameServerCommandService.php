<?php

namespace App\Services\GameServer;

use App\Contracts\GameServer\GameServerCommandRepository;
use App\Data\GameServer\GameServerCommandRequest;
use App\Data\GameServer\GameServerCommandSubmission;
use App\Models\User;

final class SubmitGameServerCommandService
{
    public function __construct(private readonly GameServerCommandRepository $commands) {}

    public function submit(GameServerCommandRequest $request, User $operator): GameServerCommandSubmission
    {
        return $this->commands->submit($request, $operator->getKey());
    }
}
