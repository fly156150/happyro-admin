<?php

namespace App\Data\GameServer;

final readonly class GameServerSettingDefinition
{
    public function __construct(
        public string $key,
        public int $minimum,
        public int $maximum,
        public string $source,
        public string $unit = 'percent',
    ) {}
}
