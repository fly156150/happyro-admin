<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\Auth\AuthenticationService;
use App\Data\Auth\ClientContext;
use App\Data\Auth\LoginStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\CurrentUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SessionController extends Controller
{
    public function __construct(private readonly AuthenticationService $authentication) {}

    public function store(LoginRequest $request): JsonResponse
    {
        $result = $this->authentication->login($request->loginData(), $request->clientContext());

        if ($result->status === LoginStatus::RateLimited) {
            return response()->json([
                'message' => '登录尝试次数过多，请稍后再试。',
                'retry_after' => $result->retryAfter,
            ], 429)->header('Retry-After', (string) $result->retryAfter);
        }

        if ($result->status === LoginStatus::InvalidCredentials) {
            return response()->json([
                'message' => '用户名或密码错误。',
                'errors' => ['username' => ['用户名或密码错误。']],
            ], 422);
        }

        $request->session()->regenerate();

        return response()->json(['status' => 'ok']);
    }

    public function show(Request $request): CurrentUserResource
    {
        return new CurrentUserResource($request->user());
    }

    public function destroy(Request $request): Response
    {
        $this->authentication->logout(
            $request->user(),
            new ClientContext($request->ip(), $request->userAgent()),
        );
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
