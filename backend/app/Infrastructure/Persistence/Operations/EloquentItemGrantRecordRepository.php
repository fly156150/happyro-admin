<?php

namespace App\Infrastructure\Persistence\Operations;

use App\Contracts\Operations\ItemGrantRecordRepository;
use App\Models\ItemGrantRecord;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentItemGrantRecordRepository implements ItemGrantRecordRepository
{
    public function findByIdempotencyKey(string $key): ?ItemGrantRecord
    {
        return ItemGrantRecord::query()->where('idempotency_key', $key)->first();
    }

    public function createPending(string $key, array $attributes): ItemGrantRecord
    {
        return ItemGrantRecord::query()->firstOrCreate(
            ['idempotency_key' => $key],
            $attributes,
        );
    }

    public function markSent(ItemGrantRecord $record, int $mailId): void
    {
        $record->update(['status' => 'sent', 'mail_id' => $mailId]);
    }

    public function markFailed(ItemGrantRecord $record, string $error): void
    {
        $record->update(['status' => 'failed', 'error' => $error]);
    }

    public function paginate(int $perPage): LengthAwarePaginator
    {
        return ItemGrantRecord::query()
            ->with('requester:id,name,username')
            ->latest()
            ->paginate($perPage);
    }
}
