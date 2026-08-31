<?php

namespace App\Contracts\Auth;

use App\Data\Auth\ClientContext;
use App\Data\Auth\LoginData;
use App\Data\Auth\LoginResult;
use App\Models\User;

interface AuthenticationService
{
    public function login(LoginData $data, ClientContext $context): LoginResult;

    public function logout(User $user, ClientContext $context): void;
}
