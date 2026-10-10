<?php

namespace App\Services\GameServer;

use App\Contracts\GameServer\GameServerGateway;
use App\Contracts\Players\PlayerCharacterRepository;
use App\Exceptions\GameServerGatewayException;

final readonly class ReadCharacterSnapshotService
{
    public function __construct(
        private GameServerGateway $gateway,
        private PlayerCharacterRepository $characters,
    ) {}

    /** @return array<string, mixed>|null */
    public function read(int $characterId): ?array
    {
        try {
            return [...$this->gateway->characterSnapshot($characterId), 'online' => true];
        } catch (GameServerGatewayException $exception) {
            if ($exception->errorCode !== 'character_offline') {
                throw $exception;
            }
        }

        $character = $this->characters->find($characterId);
        if ($character === null) {
            return null;
        }

        return [
            ...$character,
            'online' => false,
            'job_id' => (int) $character['class'],
            'status_points' => (int) $character['status_point'],
            'skill_points' => (int) $character['skill_point'],
            'map' => $character['last_map'],
            'x' => (int) $character['last_x'],
            'y' => (int) $character['last_y'],
        ];
    }
}
