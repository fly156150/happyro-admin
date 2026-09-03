<?php

namespace App\Contracts\GameServer;

interface GameServerSettingRepository
{
    /** @param array<string, int> $values */
    public function syncApplied(string $serverKey, array $values, int $revisionId): void;

    /** @return array<string, int> */
    public function current(string $serverKey): array;
}
