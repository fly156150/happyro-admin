<?php

namespace App\Services\AdventureTools;

use App\Contracts\GameData\ItemRepository;
use App\Data\Auth\GameSessionPrincipal;
use App\Data\GameServer\GameServerCommandRequest;
use App\Data\GameServer\GameServerCommandStatus;
use App\Data\GameServer\GameServerCommandType;
use App\Exceptions\GameServerGatewayException;
use App\Services\GameServer\ExecuteGameServerCommandService;
use App\Services\GameServer\SubmitGameServerCommandService;

final class GrantAdventureItemService
{
    public function __construct(
        private readonly ItemRepository $items,
        private readonly AdventureItemGrantTargetResolver $targets,
        private readonly SubmitGameServerCommandService $submit,
        private readonly ExecuteGameServerCommandService $execute,
    ) {}

    /**
     * @param  array{idempotency_key: string, target: array{type: string}, item_id: int, amount: int}  $data
     * @return array<string, mixed>
     */
    public function grant(array $data, GameSessionPrincipal $principal): array
    {
        abort_unless($this->items->find($data['item_id'], 'server'), 422, '物品不存在或无法发放');
        $characterId = $this->targets->resolve($data['target'], $principal);
        $submission = $this->submit->submitForGameAccount(new GameServerCommandRequest(
            $data['idempotency_key'],
            GameServerCommandType::CharacterInventoryItemGrant,
            'character',
            (string) $characterId,
            ['item_id' => $data['item_id'], 'amount' => $data['amount']],
        ), $principal->accountId);
        if ($submission->created) {
            $command = $this->execute->execute($submission->command->id);
        } else {
            $command = $submission->command;
            if ($command->status !== GameServerCommandStatus::Succeeded) {
                throw new GameServerGatewayException(
                    $command->errorCode ?? 'command_not_replayable',
                    $command->errorMessage ?? 'The original item grant has not completed successfully.',
                    $command->status === GameServerCommandStatus::Indeterminate,
                );
            }
        }

        return $command->result ?? [];
    }
}
