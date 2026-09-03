<?php

namespace App\Services\Operations;

use App\Contracts\GameData\ItemRepository;
use App\Data\GameData\ItemQuery;

final class ItemGrantItemService
{
    private const RESULT_LIMIT = 20;

    public function __construct(private readonly ItemRepository $items) {}

    /** @return list<array{item_id: int, aegis_name: string|null, names: array<string, string>}> */
    public function search(string $target): array
    {
        $result = $this->items->search(new ItemQuery(
            $target,
            null,
            null,
            'server',
            1,
            self::RESULT_LIMIT,
        ));

        return array_map(fn (array $item): array => [
            'item_id' => (int) $item['Id'],
            'aegis_name' => $item['AegisName'] ?? null,
            'names' => $item['names'],
        ], $result['data']);
    }
}
