<?php

namespace App\Contracts\GameServer;

interface GameServerConfigWriter
{
    /** @param array<string, int> $changes */
    public function write(array $changes): void;

    public function snapshot(): string;

    public function restore(string $snapshot): void;
}
