<?php

namespace App\Data\GameData;

final readonly class MonsterCatalogSnapshot
{
    /** @param array<string, mixed> $metadata @param array<string, array<string, mixed>> $monsters */
    public function __construct(
        public string $version,
        public string $contentHash,
        public array $metadata,
        public array $monsters,
    ) {}
}
