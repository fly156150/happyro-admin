<?php

namespace App\Services\Operations;

use App\Contracts\Audit\AuditWriter;
use App\Contracts\GameData\ItemRepository;
use App\Contracts\Operations\ItemGrantRecordRepository;
use App\Contracts\Operations\ItemGrantRepository;
use App\Data\Auth\ClientContext;
use App\Exceptions\ItemGrantConflictException;
use App\Exceptions\ItemNotFoundException;
use App\Models\ItemGrantRecord;
use App\Models\User;
use App\Rules\GameMailText;
use Illuminate\Validation\ValidationException;

final class ItemGrantService
{
    public function __construct(
        private readonly ItemRepository $items,
        private readonly ItemGrantRepository $grants,
        private readonly ItemGrantRecordRepository $records,
        private readonly AuditWriter $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function mail(array $data, User $operator, ClientContext $context): int
    {
        $requestHash = hash('sha256', json_encode([
            'item_id' => (int) $data['item_id'], 'char_id' => (int) $data['char_id'], 'amount' => (int) $data['amount'],
            'title' => (string) $data['title'], 'message' => (string) $data['message'], 'bound' => (bool) ($data['bound'] ?? false),
        ], JSON_THROW_ON_ERROR));
        $existing = $this->records->findByIdempotencyKey($data['idempotency_key']);
        if ($existing) {
            return $this->replay($existing, $requestHash);
        }
        $item = $this->items->find((int) $data['item_id'], 'server');
        if (! $item) {
            throw new ItemNotFoundException((int) $data['item_id']);
        }
        if (($item['Type'] ?? null) === 'PetEgg' && (int) $data['amount'] !== 1) {
            throw ValidationException::withMessages(['amount' => '宠物蛋邮件每次只能发放 1 个。']);
        }
        // Source: happyro-server/src/map/itemdb.cpp::isStackable/inventorySlotNeeded;
        // PACKETVER=20211103 allows five attachments (src/common/mmo.hpp).
        $separateAttachments = in_array($item['Type'] ?? null, ['Weapon', 'Armor', 'PetEgg', 'PetArmor', 'ShadowGear'], true)
            || ($item['Flags']['UniqueId'] ?? false) === true;
        if ($separateAttachments && (int) $data['amount'] > 5) {
            throw ValidationException::withMessages(['amount' => '此物品每封邮件最多发放 5 件，请分批发放。']);
        }
        $attachmentAmounts = $separateAttachments ? array_fill(0, (int) $data['amount'], 1) : [(int) $data['amount']];
        $sender = $operator->username ?? $operator->name ?? 'admin';
        foreach (['title' => [39, '邮件标题'], 'message' => [499, '邮件内容'], 'send_name' => [23, '发件人名称']] as $field => [$maximum, $label]) {
            (new GameMailText($maximum, $label))->validate($field, $field === 'send_name' ? $sender : $data[$field], function (string $message) use ($field): void {
                throw ValidationException::withMessages([$field => $message]);
            });
        }
        $record = $this->records->createPending(
            $data['idempotency_key'],
            ['request_hash' => $requestHash, 'item_id' => $data['item_id'], 'char_id' => $data['char_id'], 'amount' => $data['amount'], 'title' => $data['title'], 'status' => 'pending', 'requested_by' => $operator->getKey()],
        );
        if (! $record->wasRecentlyCreated) {
            return $this->replay($record, $requestHash);
        }
        try {
            $mailId = $this->grants->mail([...$data, 'send_name' => $sender, 'attachment_amounts' => $attachmentAmounts]);
            $this->records->markSent($record, $mailId);
        } catch (\Throwable $exception) {
            $this->records->markFailed($record, 'item_grant_failed');
            throw $exception;
        }
        $this->audit->write('operations.item_mailed', $context, $operator, metadata: ['delivery' => 'mail', 'mail_id' => $mailId, 'item_id' => $data['item_id'], 'char_id' => $data['char_id'], 'amount' => $data['amount']]);

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
