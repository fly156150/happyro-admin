<?php

namespace App\Services\AdventureTools;

use App\Contracts\GameServer\GameServerGateway;
use App\Data\Auth\GameSessionPrincipal;

final class AdventureToolAccessService
{
    public function __construct(private readonly GameServerGateway $gateway) {}

    public function allows(string $setting, GameSessionPrincipal $principal): bool
    {
        $policy = $this->gateway->battleConfig()[$setting] ?? 2;

        return $this->allowsPolicy($policy, $principal);
    }

    public function allowsPolicy(int $policy, GameSessionPrincipal $principal): bool
    {
        return $policy === 2 || ($policy === 1 && $principal->administrator);
    }

    public function authorize(string $setting, GameSessionPrincipal $principal): void
    {
        abort_unless($this->allows($setting, $principal), 403);
    }
}
