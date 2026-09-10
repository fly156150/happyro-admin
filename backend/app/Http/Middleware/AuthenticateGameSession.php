<?php

namespace App\Http\Middleware;

use App\Services\Auth\GameSessionAuthenticationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class AuthenticateGameSession
{
    public function __construct(private GameSessionAuthenticationService $authentication) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $accountId = filter_var($request->header('X-HappyRO-Account-ID'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $characterId = filter_var($request->header('X-HappyRO-Character-ID'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $token = $request->header('X-HappyRO-Auth-Token');

        if (! is_int($accountId) || ! is_int($characterId) || ! is_string($token) || $token === '' || strlen($token) > 64) {
            return response()->json(['message' => '游戏会话无效'], 401);
        }

        $principal = $this->authentication->authenticate($accountId, $characterId, $token);
        if ($principal === null) {
            return response()->json(['message' => '游戏会话无效'], 401);
        }

        $request->attributes->set('game_session', $principal);

        return $next($request);
    }
}
