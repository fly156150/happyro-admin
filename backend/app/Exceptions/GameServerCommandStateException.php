<?php

namespace App\Exceptions;

use App\Data\GameServer\GameServerCommandStatus;
use RuntimeException;

final class GameServerCommandStateException extends RuntimeException
{
    public function __construct(string $id, GameServerCommandStatus $current, GameServerCommandStatus $next)
    {
        parent::__construct("Game server command [{$id}] cannot transition from {$current->value} to {$next->value}.");
    }
}
