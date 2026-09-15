<?php

namespace App\Data\GameData;

final readonly class NpcCatalogSnapshot
{
    /** @param array<string, mixed> $metadata @param list<array<string, mixed>> $npcs */
    public function __construct(
        public string $version,
        public string $contentHash,
        public array $metadata,
        public array $npcs,
    ) {}
}
