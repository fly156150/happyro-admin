<?php

namespace App\Http\Controllers\Players;

use App\Contracts\Players\PlayerCharacterRepository;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PlayerCharacterController
{
    public function __construct(private readonly PlayerCharacterRepository $characters) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $page = $this->characters->paginate($request->string('username')->toString() ?: null, min($request->integer('perPage', 20), 100));
        } catch (QueryException) {
            return response()->json(['message' => __('messages.player_database_unconfigured')], 503);
        }

        return response()->json(['data' => $page->items(), 'total' => $page->total(), 'current' => $page->currentPage(), 'pageSize' => $page->perPage()]);
    }

    public function show(int $charId): JsonResponse
    {
        try {
            $character = $this->characters->find($charId);
        } catch (QueryException) {
            return response()->json(['message' => __('messages.player_database_unconfigured')], 503);
        }

        return $character
            ? response()->json(['data' => $character])
            : response()->json(['message' => __('messages.character_not_found')], 404);
    }
}
