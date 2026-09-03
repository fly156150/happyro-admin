<?php

namespace App\Exceptions;

use RuntimeException;

final class GameServerCommandNotFoundException extends RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Game server command [{$id}] was not found.");
    }
}
