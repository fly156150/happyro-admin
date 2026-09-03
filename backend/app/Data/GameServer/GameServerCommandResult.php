<?php

namespace App\Data\GameServer;

final readonly class GameServerCommandResult
{
    /** @param array<string, mixed> $data */
    public function __construct(public array $data) {}
}
