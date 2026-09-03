<?php

namespace App\Http\Controllers\GameData;

use App\Contracts\GameData\MonsterAssetRepository;
use App\Contracts\GameData\MonsterRepository;
use App\Data\GameData\MonsterQuery;
use App\Http\Requests\GameData\ListMonstersRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class MonsterController
{
    public function __construct(private MonsterRepository $monsters, private MonsterAssetRepository $assets) {}

    public function index(ListMonstersRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->monsters->search(new MonsterQuery(
            $data['query'] ?? null, $data['race'] ?? null, $data['element'] ?? null,
            $data['size'] ?? null, $data['boss'] ?? null,
            $data['page'] ?? 1, $data['perPage'] ?? 20,
        ));
        $result['data'] = array_map($this->withImage(...), $result['data']);

        return response()->json([...$result, 'success' => true]);
    }

    public function show(int $monsterId): JsonResponse
    {
        $monster = $this->monsters->find($monsterId);
        abort_unless($monster, 404);

        return response()->json(['data' => $this->withImage($monster), 'success' => true]);
    }

    public function image(int $monsterId): BinaryFileResponse
    {
        $path = $this->assets->imagePath($monsterId);
        abort_unless($path, 404);

        return response()->file($path, ['Cache-Control' => 'public, max-age=86400']);
    }

    /** @param array<string, mixed> $monster @return array<string, mixed> */
    private function withImage(array $monster): array
    {
        $monster['image'] = $this->assets->imagePath((int) $monster['Id'])
            ? "/api/game-data/monsters/{$monster['Id']}/image" : null;

        return $monster;
    }
}
