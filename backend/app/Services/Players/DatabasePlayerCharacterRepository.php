<?php

namespace App\Services\Players;

use App\Contracts\Players\PlayerCharacterRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class DatabasePlayerCharacterRepository implements PlayerCharacterRepository
{
    public function paginate(?string $username, int $perPage): LengthAwarePaginator
    {
        $query = DB::connection('game')->table(config('happyro.players.character_table', 'char').' as characters')->leftJoin(config('happyro.players.account_table', 'login').' as accounts', 'accounts.account_id', '=', 'characters.account_id')->select(['characters.char_id', 'characters.account_id', 'accounts.userid as username', 'characters.name', 'characters.class', 'characters.base_level', 'characters.job_level', 'characters.last_map', 'characters.online', 'characters.last_login']);
        if ($username) {
            $query->where('accounts.userid', 'like', "%{$username}%");
        }

        return $query->orderByDesc('char_id')->paginate($perPage);
    }
}
