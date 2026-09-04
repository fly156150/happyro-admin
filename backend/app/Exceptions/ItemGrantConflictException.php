<?php

namespace App\Exceptions;

use RuntimeException;

final class ItemGrantConflictException extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
