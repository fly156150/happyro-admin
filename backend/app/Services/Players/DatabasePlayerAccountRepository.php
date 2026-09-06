<?php

namespace App\Services\Players;

use App\Contracts\Players\PlayerAccountRepository;
use App\Exceptions\PlayerAccountNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class DatabasePlayerAccountRepository implements PlayerAccountRepository
{
    private function query(): Builder
    {
        return DB::connection('game')->table(config('happyro.players.account_table', 'login'));
    }

    public function paginate(?string $username, ?string $email, ?string $status, int $perPage): LengthAwarePaginator
    {
        $query = $this->query()->select(['account_id', 'userid as username', 'email', 'sex', 'group_id', 'state', 'lastlogin', 'last_ip']);
        if ($username) {
            $query->where('userid', 'like', "%{$username}%");
        }
        if ($email) {
            $query->where('email', 'like', "%{$email}%");
        }
        if ($status === 'active') {
            $query->where('state', 0);
        }
        if ($status === 'banned') {
            $query->where('state', '>', 0);
        }

        return $query->orderByDesc('account_id')->paginate($perPage);
    }

    public function find(int $accountId): ?object
    {
        return $this->query()->where('account_id', $accountId)->first();
    }

    public function create(array $attributes): object
    {
        $id = $this->query()->insertGetId($attributes, 'account_id');

        return $this->find((int) $id);
    }

    public function update(int $accountId, array $attributes): object
    {
        $updated = $this->query()->where('account_id', $accountId)->update($attributes);
        if ($updated === 0 && $this->find($accountId) === null) {
            throw new PlayerAccountNotFoundException($accountId);
        }

        return $this->find($accountId);
    }

    public function delete(int $accountId): int
    {
        $deleted = $this->query()->where('account_id', $accountId)->delete();
        if ($deleted === 0) {
            throw new PlayerAccountNotFoundException($accountId);
        }
        DB::connection('game')->table(config('happyro.players.character_table', 'char'))
            ->where('account_id', $accountId)->delete();

        return $deleted;
    }

    public function updateState(array $accountIds, int $state): int
    {
        return $this->query()->whereIn('account_id', $accountIds)->update(['state' => $state]);
    }

    public function kick(array $accountIds): int
    {
        return DB::connection('game')->table(config('happyro.players.character_table', 'char'))
            ->whereIn('account_id', $accountIds)->where('online', 1)->update(['online' => 0]);
    }
}
