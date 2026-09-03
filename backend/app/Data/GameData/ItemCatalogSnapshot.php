<?php

namespace App\Data\GameData;

final readonly class ItemCatalogSnapshot
{
    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, array<string, mixed>>  $items
     */
    public function __construct(
        public string $source,
        public string $ruleset,
        public string $version,
        public string $contentHash,
        public array $metadata,
        public array $items,
    ) {}
}
