<?php

namespace App\Exceptions;

use RuntimeException;

final class CharacterNotFoundException extends RuntimeException
{
    public function __construct(int $charId)
    {
        parent::__construct("Character {$charId} was not found.");
    }
}
