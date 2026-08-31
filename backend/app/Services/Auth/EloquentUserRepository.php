<?php

namespace App\Services\Auth;

use App\Contracts\Auth\UserRepository;
use App\Data\Auth\ClientContext;
use App\Models\User;

final class EloquentUserRepository implements UserRepository
{
    public function findByUsername(string $username): ?User
    {
        return User::query()->where('username', $username)->first();
    }

    public function recordLogin(User $user, ClientContext $context): void
    {
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $context->ipAddress,
        ])->save();
    }
}
