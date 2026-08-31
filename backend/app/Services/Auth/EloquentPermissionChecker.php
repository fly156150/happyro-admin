<?php

namespace App\Services\Auth;

use App\Contracts\Auth\PermissionChecker;
use App\Models\User;

final class EloquentPermissionChecker implements PermissionChecker
{
    public function allows(User $user, string $permission): bool
    {
        return $user->hasPermission($permission);
    }
}
