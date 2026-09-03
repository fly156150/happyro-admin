<?php

namespace App\Exceptions;

use RuntimeException;

final class IdempotencyConflictException extends RuntimeException
{
    public function __construct(string $key)
    {
        parent::__construct("The idempotency key [{$key}] is already associated with another request.");
    }
}
