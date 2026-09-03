<?php

namespace App\Data\GameData;

final readonly class ItemQuery
{
    public function __construct(
        public ?string $query,
        public ?string $type,
        public ?string $subtype,
        public string $range,
        public int $page,
        public int $perPage,
    ) {}
}
