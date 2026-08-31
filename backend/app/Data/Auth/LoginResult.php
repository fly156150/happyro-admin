<?php

namespace App\Data\Auth;

use App\Models\User;

final readonly class LoginResult
{
    private function __construct(
        public LoginStatus $status,
        public ?User $user = null,
        public int $retryAfter = 0,
    ) {}

    public static function success(User $user): self
    {
        return new self(LoginStatus::Success, $user);
    }

    public static function invalidCredentials(): self
    {
        return new self(LoginStatus::InvalidCredentials);
    }

    public static function rateLimited(int $retryAfter): self
    {
        return new self(LoginStatus::RateLimited, retryAfter: $retryAfter);
    }
}
