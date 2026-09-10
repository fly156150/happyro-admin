<?php

namespace App\Services\Operations;

use App\Contracts\Audit\AuditWriter;
use App\Contracts\GameData\ItemRepository;
use App\Data\Auth\ClientContext;
use App\Data\GameServer\GameServerCommandRequest;
use App\Data\GameServer\GameServerCommandStatus;
use App\Data\GameServer\GameServerCommandType;
use App\Exceptions\GameServerGatewayException;
use App\Exceptions\ItemNotFoundException;
use App\Models\User;
use App\Services\GameServer\ExecuteGameServerCommandService;
use App\Services\GameServer\SubmitGameServerCommandService;

final class GrantCharacterInventoryItemService
{
    public function __construct(
        private readonly ItemRepository $items,
        private readonly SubmitGameServerCommandService $submit,
        private readonly ExecuteGameServerCommandService $execute,
        private readonly AuditWriter $audit,
    ) {}

    /** @param array{item_id:int,char_id:int,amount:int,idempotency_key:string} $data */
    public function grant(array $data, User $operator, ClientContext $context): array
    {
        $item = $this->items->find($data['item_id'], 'server');
        if (! $item) {
            throw new ItemNotFoundException($data['item_id']);
        }

        $submission = $this->submit->submit(new GameServerCommandRequest(
            $data['idempotency_key'],
            GameServerCommandType::CharacterInventoryItemGrant,
            'character',
            (string) $data['char_id'],
            [
                'item_id' => $data['item_id'],
                'amount' => $data['amount'],
                'identify' => in_array($item['Type'] ?? null, ['Weapon', 'Armor', 'PetArmor', 'ShadowGear'], true),
            ],
        ), $operator);

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

        $this->audit->write('operations.item_granted_inventory', $context, $operator, metadata: [
            'command_id' => $command->id,
            'item_id' => $data['item_id'],
            'char_id' => $data['char_id'],
            'amount' => $data['amount'],
        ]);

        return $command->result ?? [];
    }
}
