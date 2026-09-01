<?php

namespace App\Http\Controllers\Resources;

use App\Contracts\Resources\ItemCatalog;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\Request;

final class ItemController
{
    public function __construct(private readonly ItemCatalog $catalog) {}

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['query' => ['nullable', 'string', 'max:100'], 'type' => ['nullable', 'string', 'max:30'], 'page' => ['nullable', 'integer', 'min:1'], 'perPage' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $result = $this->catalog->search($data['query'] ?? null, $data['type'] ?? null, $data['page'] ?? 1, $data['perPage'] ?? 20);

        $result['data'] = array_map(fn (array $item): array => $this->withIconUrl($item), $result['data']);

        return response()->json([...$result, 'success' => true]);
    }

    public function show(int $id): JsonResponse
    {
        $item = $this->catalog->find($id);
        abort_unless($item, 404);

        $item = $this->withIconUrl($item);
        $item['description'] = $this->catalog->description($id);

        return response()->json(['data' => $item, 'success' => true]);
    }

    public function icon(int $id): BinaryFileResponse
    {
        $path = $this->catalog->iconPath($id);
        abort_unless($path, 404);

        return response()->file($path, ['Cache-Control' => 'public, max-age=86400']);
    }

    public function illustration(int $id): BinaryFileResponse
    {
        $path = $this->catalog->illustrationPath($id);
        abort_unless($path, 404);

        return response()->file($path, ['Cache-Control' => 'public, max-age=86400']);
    }

    private function withIconUrl(array $item): array
    {
        $item['icon'] = $this->catalog->iconPath((int) $item['Id'])
            ? "/api/resources/items/{$item['Id']}/icon"
            : '/game/items/item-placeholder.bmp';
        $item['illustration'] = $this->catalog->illustrationPath((int) $item['Id'])
            ? "/api/resources/items/{$item['Id']}/illustration"
            : null;

        return $item;
    }
}
