<?php

namespace App\Contracts\Players;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PlayerAccountRepository
{
    public function paginate(?string $username, ?string $email, ?string $status, int $perPage): LengthAwarePaginator;

    public function find(int $accountId): ?object;

    public function create(array $attributes): object;

    public function update(int $accountId, array $attributes): object;

    public function delete(int $accountId): int;

    public function updateState(array $accountIds, int $state): int;

    public function kick(array $accountIds): int;
}
