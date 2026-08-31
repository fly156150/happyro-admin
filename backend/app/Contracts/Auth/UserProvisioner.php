<?php

namespace App\Contracts\Auth;

use App\Data\Auth\CreateUserData;
use App\Models\User;

interface UserProvisioner
{
    public function create(CreateUserData $data): User;
}
