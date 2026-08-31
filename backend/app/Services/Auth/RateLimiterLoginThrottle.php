<?php

namespace App\Services\Auth;

use App\Contracts\Auth\LoginThrottle;
use App\Data\Auth\ClientContext;
use App\Data\Auth\LoginData;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Str;

final class RateLimiterLoginThrottle implements LoginThrottle
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function __construct(private readonly RateLimiter $limiter) {}

    public function isLocked(LoginData $data, ClientContext $context): bool
    {
        return $this->limiter->tooManyAttempts($this->key($data, $context), self::MAX_ATTEMPTS);
    }

    public function hit(LoginData $data, ClientContext $context): void
    {
        $this->limiter->hit($this->key($data, $context), self::DECAY_SECONDS);
    }

    public function clear(LoginData $data, ClientContext $context): void
    {
        $this->limiter->clear($this->key($data, $context));
    }

    public function availableIn(LoginData $data, ClientContext $context): int
    {
        return $this->limiter->availableIn($this->key($data, $context));
    }

    private function key(LoginData $data, ClientContext $context): string
    {
        return Str::lower($data->username).'|'.($context->ipAddress ?? 'unknown');
    }
}
