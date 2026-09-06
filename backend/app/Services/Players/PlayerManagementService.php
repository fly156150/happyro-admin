<?php

namespace App\Services\Players;

use App\Contracts\Audit\AuditWriter;
use App\Contracts\Players\PlayerAccountRepository;
use App\Data\Auth\ClientContext;
use App\Exceptions\PlayerAccountNotFoundException;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class PlayerManagementService
{
    public function __construct(private readonly PlayerAccountRepository $accounts, private readonly AuditWriter $audit, private readonly Hasher $hasher) {}

    public function paginate(?string $username, ?string $email, ?string $status, int $perPage): LengthAwarePaginator
    {
        return $this->accounts->paginate($username, $email, $status, $perPage);
    }

    public function register(array $attributes, User $operator, ClientContext $context): object
    {
        $account = $this->accounts->create($attributes);
        $this->audit->write('player.registered', $context, $operator, metadata: ['account_id' => $account->account_id ?? null]);

        return $account;
    }

    public function update(int $id, array $attributes, User $operator, ClientContext $context): object
    {
        $account = $this->accounts->update($id, $attributes);
        $this->audit->write('player.updated', $context, $operator, metadata: ['account_id' => $id, 'fields' => array_keys($attributes)]);

        return $account;
    }

    public function resetPassword(int $id, string $password, User $operator, ClientContext $context): object
    {
        $value = config('happyro.players.password_hash') ? $this->hasher->make($password) : $password;
        $account = $this->update($id, ['user_pass' => $value], $operator, $context);
        $this->audit->write('player.password_reset', $context, $operator, metadata: ['account_id' => $id]);

        return $account;
    }

    public function delete(int $id, User $operator, ClientContext $context): void
    {
        $this->accounts->delete($id);
        $this->audit->write('player.deleted', $context, $operator, metadata: ['account_id' => $id]);
    }

    public function batch(string $action, array $ids, User $operator, ClientContext $context): int
    {
        $count = match ($action) {
            'delete' => $this->deleteMany($ids),
            'ban' => $this->accounts->updateState($ids, 1),
            'unban' => $this->accounts->updateState($ids, 0),
            'kick' => $this->accounts->kick($ids),
            default => throw new \InvalidArgumentException('Unsupported player action.'),
        };
        $this->audit->write('player.batch_'.$action, $context, $operator, metadata: ['account_ids' => $ids, 'count' => $count]);

        return $count;
    }

    private function deleteMany(array $ids): int
    {
        $count = 0;
        foreach (array_values(array_unique($ids)) as $id) {
            try {
                $count += $this->accounts->delete($id);
            } catch (PlayerAccountNotFoundException) {
                continue;
            }
        }

        return $count;
    }
}
