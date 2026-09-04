<?php

namespace App\Services\Operations;

use App\Contracts\Audit\AuditWriter;
use App\Contracts\GameData\ItemRepository;
use App\Contracts\Operations\ItemGrantRepository;
use App\Data\Auth\ClientContext;
use App\Exceptions\ItemGrantConflictException;
use App\Exceptions\ItemNotFoundException;
use App\Models\ItemGrantRecord;
use App\Models\User;

final class ItemGrantService
{
    public function __construct(private readonly ItemRepository $items, private readonly ItemGrantRepository $grants, private readonly AuditWriter $audit) {}

    /** @param array<string, mixed> $data */
    public function mail(array $data, User $operator, ClientContext $context): int
    {
        $requestHash = hash('sha256', json_encode([
            'item_id' => (int) $data['item_id'], 'char_id' => (int) $data['char_id'], 'amount' => (int) $data['amount'],
            'title' => (string) $data['title'], 'message' => (string) $data['message'], 'bound' => (bool) ($data['bound'] ?? false),
        ], JSON_THROW_ON_ERROR));
        $existing = ItemGrantRecord::query()->where('idempotency_key', $data['idempotency_key'])->first();
        if ($existing) {
            return $this->replay($existing, $requestHash);
        }
        if (! $this->items->find(
            (int) $data['item_id'],
            'server',
        )) {
            throw new ItemNotFoundException((int) $data['item_id']);
        }
        $record = ItemGrantRecord::query()->firstOrCreate(
            ['idempotency_key' => $data['idempotency_key']],
            ['request_hash' => $requestHash, 'item_id' => $data['item_id'], 'char_id' => $data['char_id'], 'amount' => $data['amount'], 'title' => $data['title'], 'status' => 'pending', 'requested_by' => $operator->getKey()],
        );
        if (! $record->wasRecentlyCreated) {
            return $this->replay($record, $requestHash);
        }
        try {
            $mailId = $this->grants->mail($data + ['send_name' => $operator->username ?? $operator->name ?? 'admin']);
            $record->update(['status' => 'sent', 'mail_id' => $mailId]);
        } catch (\Throwable $exception) {
            $record->update(['status' => 'failed', 'error' => 'item_grant_failed']);
            throw $exception;
        }
        $this->audit->write('operations.item_mailed', $context, $operator, metadata: ['mail_id' => $mailId, 'item_id' => $data['item_id'], 'char_id' => $data['char_id'], 'amount' => $data['amount']]);

        return $mailId;
    }

    private function replay(ItemGrantRecord $record, string $requestHash): int
    {
        if (! hash_equals($record->request_hash, $requestHash)) {
            throw new ItemGrantConflictException('item_grant_idempotency_conflict');
        }
        if ($record->status === 'sent' && $record->mail_id) {
            return (int) $record->mail_id;
        }
        throw new ItemGrantConflictException('item_grant_not_replayable');
    }
}
