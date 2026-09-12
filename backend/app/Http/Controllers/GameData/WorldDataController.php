<?php

namespace App\Http\Controllers\GameData;

use App\Services\GameData\WorldDataService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class WorldDataController
{
    public function __construct(private readonly WorldDataService $world) {}

    public function maps(): JsonResponse
    {
        $rows = $this->world->maps();

        return response()->json(['data' => $rows, 'total' => count($rows), 'success' => true]);
    }

    public function npcs(): JsonResponse
    {
        $rows = $this->world->npcs();

        return response()->json(['data' => $rows, 'total' => count($rows), 'success' => true]);
    }

    public function mapImage(string $map): BinaryFileResponse
    {
        abort_unless(preg_match('/^[a-z0-9_@-]+$/', $map) === 1, 404);
        $path = base_path("resources/game-data/world/maps/{$map}.png");
        abort_unless(is_file($path), 404);

        return response()->file($path, ['Cache-Control' => 'public, max-age=86400']);
    }

    public function npcImage(int $spriteId): BinaryFileResponse
    {
        $path = base_path("resources/game-data/world/npcs/{$spriteId}.png");
        abort_unless(is_file($path), 404);

        return response()->file($path, ['Cache-Control' => 'public, max-age=86400']);
    }
}
