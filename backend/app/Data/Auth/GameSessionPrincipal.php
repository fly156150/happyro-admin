<?php

namespace App\Data\Auth;

final readonly class GameSessionPrincipal
{
    public function __construct(
        public int $accountId,
        public int $characterId,
        public int $groupId,
        public bool $administrator,
    ) {}
}
