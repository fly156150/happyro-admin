<?php

namespace App\Data\Auth;

final readonly class ClientContext
{
    public function __construct(
        public ?string $ipAddress,
        public ?string $userAgent,
    ) {}
}
