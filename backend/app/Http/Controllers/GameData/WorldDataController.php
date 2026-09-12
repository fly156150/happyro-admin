<?php

namespace App\Http\Controllers\GameData;

use App\Contracts\GameServer\GameServerGateway;
use App\Exceptions\GameServerGatewayException;
use App\Services\GameData\WorldDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class WorldDataController
{
    public function __construct(private readonly WorldDataService $world) {}

    public function maps(Request $request, GameServerGateway $gateway): JsonResponse
    {
        $scope = $request->query('scope', 'game');
        abort_unless(in_array($scope, ['game', 'all'], true), 422);
        $channelsEnabled = false;
        if ($scope === 'game') {
            try {
                $channelsEnabled = (bool) ($gateway->battleConfig()['navigation_map_channels_enabled'] ?? false);
            } catch (GameServerGatewayException $exception) {
                return response()->json(['message' => '暂时无法读取游戏地图配置，请重试或查看全部服务端地图。'], 503);
            }
        }
        $rows = $this->world->maps($scope === 'game', $channelsEnabled);

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
        $catalog = json_decode(file_get_contents(base_path('resources/game-data/world/map-catalog.json')), true, flags: JSON_THROW_ON_ERROR);
        $entry = array_column($catalog['entries'], null, 'map')[$map] ?? null;
        $imageMap = $entry['image_map'] ?? $map;
        $folder = ($entry['image_kind'] ?? null) === 'terrain' ? 'terrain' : 'maps';
        $path = base_path("resources/game-data/world/{$folder}/{$imageMap}.png");
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
