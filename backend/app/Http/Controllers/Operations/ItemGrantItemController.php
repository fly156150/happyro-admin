<?php

namespace App\Http\Controllers\Operations;

use App\Http\Requests\Operations\SearchItemGrantItemsRequest;
use App\Services\Operations\ItemGrantItemService;
use Illuminate\Http\JsonResponse;

final class ItemGrantItemController
{
    public function __construct(private readonly ItemGrantItemService $items) {}

    public function index(SearchItemGrantItemsRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->items->search($request->validated('target')),
            'success' => true,
        ]);
    }
}
