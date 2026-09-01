<?php

namespace App\Contracts\Resources;

interface ItemCatalog
{
    /** @return array{data: array<int, array<string, mixed>>, total: int} */
    public function search(?string $query, ?string $type, int $page, int $perPage): array;

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array;

    public function iconPath(int $id): ?string;

    public function illustrationPath(int $id): ?string;

    /** @return list<string>|null */
    public function description(int $id): ?array;
}
