<?php

namespace App\Exceptions;

use RuntimeException;

final class ItemNotFoundException extends RuntimeException
{
    public function __construct(int $itemId)
    {
        parent::__construct("Item {$itemId} was not found.");
    }
}
