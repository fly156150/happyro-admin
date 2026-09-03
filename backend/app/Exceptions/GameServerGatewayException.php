<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

final class GameServerGatewayException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly bool $outcomeUnknown = false,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
