<?php

namespace App\Data\GameData;

final readonly class GameDataVersions
{
    public function __construct(public string $client, public string $server) {}

    /** @return array{clientVersion: string, serverVersion: string} */
    public function toArray(): array
    {
        return ['clientVersion' => $this->client, 'serverVersion' => $this->server];
    }
}
