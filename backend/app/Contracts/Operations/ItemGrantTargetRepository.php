<?php

namespace App\Contracts\Operations;

interface ItemGrantTargetRepository
{
    /** @return list<array{char_id: int, name: string, username: string}> */
    public function search(string $target, int $limit): array;
}
