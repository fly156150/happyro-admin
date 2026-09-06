<?php

namespace App\Contracts\Operations;

use App\Models\ItemGrantRecord;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ItemGrantRecordRepository
{
    public function findByIdempotencyKey(string $key): ?ItemGrantRecord;

    /** @param array<string, mixed> $attributes */
    public function createPending(string $key, array $attributes): ItemGrantRecord;

    public function markSent(ItemGrantRecord $record, int $mailId): void;

    public function markFailed(ItemGrantRecord $record, string $error): void;

    /** @return LengthAwarePaginator<int, ItemGrantRecord> */
    public function paginate(int $perPage): LengthAwarePaginator;
}
