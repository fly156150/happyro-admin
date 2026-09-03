<?php

namespace App\Services\Players;

use App\Contracts\Players\PlayerCharacterRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class DatabasePlayerCharacterRepository implements PlayerCharacterRepository
{
    public function find(int $charId): ?array
    {
        $row = DB::connection('game')->table(config('happyro.players.character_table', 'char').' as characters')
            ->leftJoin(config('happyro.players.account_table', 'login').' as accounts', 'accounts.account_id', '=', 'characters.account_id')
            ->where('characters.char_id', $charId)
            ->select([
                'characters.char_id', 'characters.account_id', 'accounts.userid as username', 'characters.name', 'characters.class',
                'characters.base_level', 'characters.job_level', 'characters.base_exp', 'characters.job_exp', 'characters.zeny',
                'characters.str', 'characters.agi', 'characters.vit', 'characters.int', 'characters.dex', 'characters.luk',
                'characters.max_hp', 'characters.hp', 'characters.max_sp', 'characters.sp', 'characters.status_point', 'characters.skill_point',
                'characters.last_map', 'characters.last_x', 'characters.last_y', 'characters.online', 'characters.last_login',
            ])->first();

        return $row === null ? null : (array) $row;
    }

    public function paginate(?string $username, int $perPage): LengthAwarePaginator
    {
        $query = DB::connection('game')->table(config('happyro.players.character_table', 'char').' as characters')->leftJoin(config('happyro.players.account_table', 'login').' as accounts', 'accounts.account_id', '=', 'characters.account_id')->select(['characters.char_id', 'characters.account_id', 'accounts.userid as username', 'characters.name', 'characters.class', 'characters.base_level', 'characters.job_level', 'characters.last_map', 'characters.online', 'characters.last_login']);
        if ($username) {
            $query->where(function ($query) use ($username): void {
                $query->where('accounts.userid', 'like', "%{$username}%")
                    ->orWhere('characters.name', 'like', "%{$username}%");
            });
        }

        return $query->orderByDesc('char_id')->paginate($perPage);
    }
}
