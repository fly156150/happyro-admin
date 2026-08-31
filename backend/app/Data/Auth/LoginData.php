<?php

namespace App\Data\Auth;

final readonly class LoginData
{
    public function __construct(
        public string $username,
        public string $password,
        public bool $remember,
    ) {}
}
