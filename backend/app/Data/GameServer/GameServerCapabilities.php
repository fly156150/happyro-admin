<?php

namespace App\Data\GameServer;

final readonly class GameServerCapabilities
{
    /** @param list<string> $commands */
    public function __construct(
        public string $protocolVersion,
        public array $commands,
    ) {}

    public function supports(GameServerCommandType $type): bool
    {
        return in_array($type->value, $this->commands, true);
    }
}
