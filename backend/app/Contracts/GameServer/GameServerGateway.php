<?php

namespace App\Contracts\GameServer;

use App\Data\GameServer\GameServerCapabilities;
use App\Data\GameServer\GameServerCommand;
use App\Data\GameServer\GameServerCommandResult;

interface GameServerGateway
{
    public function capabilities(): GameServerCapabilities;

    /** @return array<string, int> */
    public function battleConfig(): array;

    public function execute(GameServerCommand $command): GameServerCommandResult;
}
