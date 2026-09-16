<?php

namespace App\Services\AdventureTools;

use App\Contracts\GameData\ItemRepository;
use App\Data\Auth\GameSessionPrincipal;
use App\Data\GameServer\GameServerCommandRequest;
use App\Data\GameServer\GameServerCommandType;
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
        $item = $this->items->find($data['item_id'], 'server');
        abort_unless($item, 422, '物品不存在或无法发放');
        $characterId = $this->targets->resolve($data['target'], $principal);
        $submission = $this->submit->submitForGameAccount(new GameServerCommandRequest(
            $data['idempotency_key'],
            GameServerCommandType::CharacterInventoryItemGrant,
            'character',
            (string) $characterId,
            [
                'item_id' => $data['item_id'],
                'amount' => $data['amount'],
                'identify' => in_array($item['Type'] ?? null, ['Weapon', 'Armor', 'PetArmor', 'ShadowGear'], true),
            ],
        ), $principal->accountId);
        $command = $this->execute->complete($submission);

        return $command->result ?? [];
    }
}
