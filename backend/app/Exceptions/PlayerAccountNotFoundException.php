<?php

namespace App\Exceptions;

use RuntimeException;

final class PlayerAccountNotFoundException extends RuntimeException
{
    public function __construct(int $accountId)
    {
        parent::__construct("Player account {$accountId} was not found.");
    }
}
