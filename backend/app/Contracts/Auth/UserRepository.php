<?php

namespace App\Contracts\Auth;

use App\Data\Auth\ClientContext;
use App\Models\User;

interface UserRepository
{
    public function findByUsername(string $username): ?User;

    public function recordLogin(User $user, ClientContext $context): void;
}
