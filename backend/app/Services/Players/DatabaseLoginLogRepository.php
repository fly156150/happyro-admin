<?php

namespace App\Services\Players;

use App\Contracts\Players\LoginLogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class DatabaseLoginLogRepository implements LoginLogRepository
{
    public function paginate(?string $username, int $perPage): LengthAwarePaginator
    {
        $query = DB::connection('game_log')->table(config('happyro.players.login_log_table', 'loginlog'))->select(['time', 'ip', 'user as username', 'rcode', 'log']);
        if ($username) {
            $query->where('user', 'like', "%{$username}%");
        }

        return $query->orderByDesc('time')->paginate($perPage);
    }
}
