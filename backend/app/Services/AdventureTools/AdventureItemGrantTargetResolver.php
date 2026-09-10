<?php

namespace App\Services\AdventureTools;

use App\Data\Auth\GameSessionPrincipal;
use InvalidArgumentException;

final class AdventureItemGrantTargetResolver
{
    /** @param array{type: string} $target */
    public function resolve(array $target, GameSessionPrincipal $principal): int
    {
        return match ($target['type']) {
            'self' => $principal->characterId,
            default => throw new InvalidArgumentException('Unsupported item grant target.'),
        };
    }
}
