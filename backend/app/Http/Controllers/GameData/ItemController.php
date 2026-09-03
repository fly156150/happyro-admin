<?php

namespace App\Http\Controllers\GameData;

use App\Contracts\GameData\ItemAssetRepository;
use App\Contracts\GameData\ItemRepository;
use App\Data\GameData\ItemQuery;
use App\Http\Requests\GameData\ListItemsRequest;
use App\Http\Requests\GameData\ShowItemRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ItemController
{
    public function __construct(
        private readonly ItemRepository $items,
        private readonly ItemAssetRepository $assets,
    ) {}

    public function index(ListItemsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->items->search(new ItemQuery(
            $data['query'] ?? null,
            $data['type'] ?? null,
            $data['subtype'] ?? null,
            $data['range'] ?? 'client',
            $data['page'] ?? 1,
            $data['perPage'] ?? 20,
        ));
        $result['data'] = array_map($this->withAssetUrls(...), $result['data']);

        return response()->json([...$result, 'success' => true]);
    }

    public function show(ShowItemRequest $request, int $itemId): JsonResponse
    {
        $data = $request->validated();
        $item = $this->items->find($itemId, $data['range'] ?? 'client');
        abort_unless($item, 404);

        $item = $this->withAssetUrls($item);

        return response()->json(['data' => $item, 'success' => true]);
    }

    public function icon(int $itemId): BinaryFileResponse
    {
        $path = $this->assets->iconPath($itemId);
        abort_unless($path, 404);

        return response()->file($path, ['Cache-Control' => 'public, max-age=86400']);
    }

    public function illustration(int $itemId): BinaryFileResponse
    {
        $path = $this->assets->illustrationPath($itemId);
        abort_unless($path, 404);

        return response()->file($path, ['Cache-Control' => 'public, max-age=86400']);
    }

    private function withAssetUrls(array $item): array
    {
        $item['icon'] = $this->assets->iconPath((int) $item['Id'])
            ? "/api/game-data/items/{$item['Id']}/icon"
            : '/game/items/item-placeholder.bmp';
        $item['illustration'] = $this->assets->illustrationPath((int) $item['Id'])
            ? "/api/game-data/items/{$item['Id']}/illustration"
            : null;

        return $item;
    }
}
