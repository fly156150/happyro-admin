<?php

namespace App\Http\Controllers\Operations;

use App\Http\Requests\Operations\SearchItemGrantTargetsRequest;
use App\Services\Operations\ItemGrantTargetService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

final class ItemGrantTargetController
{
    public function __construct(private readonly ItemGrantTargetService $targets) {}

    public function index(SearchItemGrantTargetsRequest $request): JsonResponse
    {
        try {
            $targets = $this->targets->search($request->validated('target'));
        } catch (QueryException) {
            return response()->json(['message' => __('messages.player_database_unconfigured')], 503);
        }

        return response()->json(['data' => $targets, 'success' => true]);
    }
}
