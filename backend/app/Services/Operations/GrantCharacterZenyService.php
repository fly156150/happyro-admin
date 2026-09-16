<?php

namespace App\Services\Operations;

use App\Data\GameServer\GameServerCommandRequest;
use App\Data\GameServer\GameServerCommandType;
use App\Data\GameServer\OperationActor;
use App\Services\GameServer\ExecuteGameServerCommandService;
use App\Services\GameServer\SubmitGameServerCommandService;

final class GrantCharacterZenyService
{
    public function __construct(
        private readonly SubmitGameServerCommandService $submit,
        private readonly ExecuteGameServerCommandService $execute,
    ) {}

    /** @return array<string, mixed> */
    public function grant(string $idempotencyKey, int $characterId, int $amount, OperationActor $actor): array
    {
        $submission = $this->submit->submitForActor(new GameServerCommandRequest(
            $idempotencyKey,
            GameServerCommandType::CharacterCurrencyZenyGrant,
            'character',
            (string) $characterId,
            ['amount' => $amount],
        ), $actor);
        $command = $this->execute->complete($submission);

        return $command->result ?? [];
    }
}
