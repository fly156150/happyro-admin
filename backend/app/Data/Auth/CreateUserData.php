<?php

namespace App\Data\Auth;

final readonly class CreateUserData
{
    public function __construct(
        public string $username,
        public string $name,
        public string $password,
        public string $role,
    ) {}
}
