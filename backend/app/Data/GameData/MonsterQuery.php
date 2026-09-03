<?php

namespace App\Data\GameData;

final readonly class MonsterQuery
{
    public function __construct(
        public ?string $query,
        public ?string $race,
        public ?string $element,
        public ?string $size,
        public ?bool $boss,
        public int $page,
        public int $perPage,
    ) {}
}
