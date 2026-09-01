<?php

namespace App\Services\Resources;

use App\Contracts\Audit\AuditWriter;
use App\Contracts\Resources\ItemCatalog;
use App\Contracts\Resources\ItemGrantRepository;
use App\Data\Auth\ClientContext;
use App\Exceptions\ItemNotFoundException;
use App\Models\User;

final class ItemGrantService
{
    public function __construct(private readonly ItemCatalog $catalog, private readonly ItemGrantRepository $grants, private readonly AuditWriter $audit) {}

    /** @param array<string, mixed> $data */
    public function mail(array $data, User $operator, ClientContext $context): int
    {
        if (!$this->catalog->find((int) $data['item_id'])) {
            throw new ItemNotFoundException((int) $data['item_id']);
        }
        $mailId = $this->grants->mail($data + ['send_name' => $operator->username ?? $operator->name ?? 'admin']);
        $this->audit->write('resource.item_mailed', $context, $operator, metadata: ['mail_id' => $mailId, 'item_id' => $data['item_id'], 'char_id' => $data['char_id'], 'amount' => $data['amount']]);

        return $mailId;
    }
}
