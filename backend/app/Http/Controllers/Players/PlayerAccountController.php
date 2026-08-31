<?php

namespace App\Http\Controllers\Players;

use App\Data\Auth\ClientContext;
use App\Exceptions\PlayerAccountNotFoundException;
use App\Services\Players\PlayerManagementService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PlayerAccountController
{
    public function __construct(private readonly PlayerManagementService $players) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $state = $request->string('state')->toString();
            $status = match ($state) {
                '0' => 'active', '1' => 'banned', default => null
            };
            $page = $this->players->paginate(
                $request->string('username')->toString() ?: null,
                $request->string('email')->toString() ?: null,
                $status,
                min($request->integer('perPage', 20), 100),
            );
        } catch (QueryException) {
            return response()->json(['message' => __('messages.player_database_unconfigured')], 503);
        }

        return response()->json(['data' => $page->items(), 'total' => $page->total(), 'current' => $page->currentPage(), 'pageSize' => $page->perPage()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['userid' => ['required', 'string', 'min:4', 'max:23'], 'user_pass' => ['required', 'string', 'min:8', 'max:72'], 'email' => ['required', 'email', 'max:39'], 'sex' => ['required', 'in:M,F']]);
        try {
            $result = $this->players->register($data, $request->user(), new ClientContext($request->ip(), (string) $request->userAgent()));
        } catch (QueryException) {
            return response()->json(['message' => __('messages.player_database_unconfigured')], 503);
        }

        return response()->json(['data' => $result], 201);
    }

    public function update(Request $request, int $accountId): JsonResponse
    {
        $data = $request->validate(['email' => ['sometimes', 'email', 'max:39'], 'state' => ['sometimes', 'integer', 'min:0']]);

        try {
            $result = $this->players->update($accountId, $data, $request->user(), new ClientContext($request->ip(), (string) $request->userAgent()));
        } catch (PlayerAccountNotFoundException) {
            return response()->json(['message' => __('messages.player_account_not_found')], 404);
        } catch (QueryException) {
            return response()->json(['message' => __('messages.player_database_unconfigured')], 503);
        }

        return response()->json(['data' => $result]);
    }

    public function password(Request $request, int $accountId): JsonResponse
    {
        $data = $request->validate(['password' => ['required', 'string', 'min:8', 'max:72']]);

        try {
            $result = $this->players->resetPassword($accountId, $data['password'], $request->user(), new ClientContext($request->ip(), (string) $request->userAgent()));
        } catch (PlayerAccountNotFoundException) {
            return response()->json(['message' => __('messages.player_account_not_found')], 404);
        } catch (QueryException) {
            return response()->json(['message' => __('messages.player_database_unconfigured')], 503);
        }

        return response()->json(['data' => $result]);
    }

    public function destroy(Request $request, int $accountId): JsonResponse
    {
        try {
            $this->players->delete($accountId, $request->user(), $this->context($request));
        } catch (PlayerAccountNotFoundException) {
            return response()->json(['message' => __('messages.player_account_not_found')], 404);
        } catch (QueryException) {
            return response()->json(['message' => __('messages.player_database_unconfigured')], 503);
        }

        return response()->json(['status' => 'ok']);
    }

    public function batch(Request $request): JsonResponse
    {
        $data = $request->validate(['action' => ['required', 'in:delete,ban,unban,kick'], 'account_ids' => ['required', 'array', 'min:1'], 'account_ids.*' => ['integer', 'min:1']]);
        try {
            $count = $this->players->batch($data['action'], $data['account_ids'], $request->user(), $this->context($request));
        } catch (QueryException) {
            return response()->json(['message' => __('messages.player_database_unconfigured')], 503);
        }

        return response()->json(['status' => 'ok', 'count' => $count]);
    }

    private function context(Request $request): ClientContext
    {
        return new ClientContext($request->ip(), (string) $request->userAgent());
    }
}
