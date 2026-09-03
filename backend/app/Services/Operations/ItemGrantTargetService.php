<?php

namespace App\Services\Operations;

use App\Contracts\Operations\ItemGrantTargetRepository;

final class ItemGrantTargetService
{
    private const RESULT_LIMIT = 20;

    public function __construct(private readonly ItemGrantTargetRepository $targets) {}

    /** @return list<array{char_id: int, name: string, username: string}> */
    public function search(string $target): array
    {
        return $this->targets->search($target, self::RESULT_LIMIT);
    }
}
