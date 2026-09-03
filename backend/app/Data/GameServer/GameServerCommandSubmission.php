<?php

namespace App\Data\GameServer;

final readonly class GameServerCommandSubmission
{
    public function __construct(
        public GameServerCommand $command,
        public bool $created,
    ) {}
}
