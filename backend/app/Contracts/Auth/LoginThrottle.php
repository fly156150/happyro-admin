<?php

namespace App\Contracts\Auth;

use App\Data\Auth\ClientContext;
use App\Data\Auth\LoginData;

interface LoginThrottle
{
    public function isLocked(LoginData $data, ClientContext $context): bool;

    public function hit(LoginData $data, ClientContext $context): void;

    public function clear(LoginData $data, ClientContext $context): void;

    public function availableIn(LoginData $data, ClientContext $context): int;
}
