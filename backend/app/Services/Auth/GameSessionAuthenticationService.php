<?php

namespace App\Services\Auth;

use App\Data\Auth\GameSessionPrincipal;
use Illuminate\Support\Facades\DB;

final class GameSessionAuthenticationService
{
    public function authenticate(int $accountId, int $characterId, string $token): ?GameSessionPrincipal
    {
        $account = DB::connection('game')
            ->table(config('happyro.players.account_table', 'login'))
            ->where('account_id', $accountId)
            ->where('web_auth_token_enabled', 1)
            ->first(['account_id', 'group_id', 'web_auth_token']);

        if ($account === null || ! is_string($account->web_auth_token) || ! hash_equals($account->web_auth_token, $token)) {
            return null;
        }

        $ownsOnlineCharacter = DB::connection('game')
            ->table(config('happyro.players.character_table', 'char'))
            ->where('char_id', $characterId)
            ->where('account_id', $accountId)
            ->where('online', 1)
            ->exists();

        if (! $ownsOnlineCharacter) {
            return null;
        }

        $groupId = (int) $account->group_id;

        return new GameSessionPrincipal(
            $accountId,
            $characterId,
            $groupId,
            in_array($groupId, config('happyro.adventure_tools.admin_group_ids', []), true),
        );
    }
}
