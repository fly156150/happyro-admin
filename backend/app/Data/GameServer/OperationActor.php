<?php

namespace App\Data\GameServer;

use App\Models\User;

final readonly class OperationActor
{
    private function __construct(
        public ?int $adminUserId,
        public ?int $gameAccountId,
    ) {}

    public static function admin(User $user): self
    {
        return new self($user->getKey(), null);
    }

    public static function gameAccount(int $accountId): self
    {
        return new self(null, $accountId);
    }
}
