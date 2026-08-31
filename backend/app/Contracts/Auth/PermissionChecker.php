<?php

namespace App\Contracts\Auth;

use App\Models\User;

interface PermissionChecker
{
    public function allows(User $user, string $permission): bool;
}
