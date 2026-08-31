<?php

namespace App\Http\Controllers\Players;

use App\Contracts\Players\LoginLogRepository;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LoginLogController
{
    public function __construct(private readonly LoginLogRepository $logs) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $page = $this->logs->paginate(
                $request->string('username')->toString() ?: null,
                min($request->integer('perPage', 20), 100),
            );
        } catch (QueryException) {
            return response()->json(['message' => __('messages.player_database_unconfigured')], 503);
        }

        return response()->json(['data' => $page->items(), 'total' => $page->total(), 'current' => $page->currentPage(), 'pageSize' => $page->perPage()]);
    }
}
