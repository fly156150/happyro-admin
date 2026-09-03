<?php

namespace App\Services\Operations;

use App\Contracts\Operations\ItemGrantTargetRepository;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class DatabaseItemGrantTargetRepository implements ItemGrantTargetRepository
{
    public function search(string $target, int $limit): array
    {
        $characters = config('happyro.players.character_table', 'char');
        $accounts = config('happyro.players.account_table', 'login');
        $escaped = addcslashes($target, '%_\\');

        return DB::connection('game')
            ->table("{$characters} as characters")
            ->join("{$accounts} as accounts", 'accounts.account_id', '=', 'characters.account_id')
            ->where(function (Builder $query) use ($target, $escaped): void {
                $query->where('characters.name', 'like', "%{$escaped}%")
                    ->orWhere('accounts.userid', 'like', "%{$escaped}%");
                if (ctype_digit($target)) {
                    $query->orWhere('characters.char_id', (int) $target);
                }
            })
            ->orderByRaw(
                'CASE WHEN characters.char_id = ? THEN 0 WHEN characters.name = ? THEN 1 WHEN accounts.userid = ? THEN 2 ELSE 3 END',
                [ctype_digit($target) ? (int) $target : -1, $target, $target],
            )
            ->orderByDesc('characters.char_id')
            ->limit($limit)
            ->get(['characters.char_id', 'characters.name', 'accounts.userid as username'])
            ->map(fn (object $character): array => [
                'char_id' => (int) $character->char_id,
                'name' => (string) $character->name,
                'username' => (string) $character->username,
            ])
            ->all();
    }
}
