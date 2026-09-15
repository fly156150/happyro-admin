<?php

namespace App\Http\Controllers\GameData;

use App\Contracts\GameServer\GameServerGateway;
use App\Data\GameData\MapQuery;
use App\Data\GameData\NpcQuery;
use App\Exceptions\GameServerGatewayException;
use App\Http\Requests\GameData\ListMapsRequest;
use App\Http\Requests\GameData\ListNpcsRequest;
use App\Services\GameData\NpcCatalogService;
use App\Services\GameData\WorldDataService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class WorldDataController
{
    public function __construct(
        private readonly WorldDataService $world,
        private readonly NpcCatalogService $npcs,
    ) {}

    public function maps(ListMapsRequest $request, GameServerGateway $gateway): JsonResponse
    {
        $data = $request->validated();
        $gameOnly = ($data['scope'] ?? 'game') === 'game';
        $channelsEnabled = false;
        if ($gameOnly) {
            try {
                $channelsEnabled = (bool) ($gateway->battleConfig()['navigation_map_channels_enabled'] ?? false);
            } catch (GameServerGatewayException) {
                return response()->json(['message' => '暂时无法读取游戏地图配置，请重试或查看全部服务端地图。'], 503);
            }
        }
        $result = $this->world->maps(new MapQuery(
            gameOnly: $gameOnly,
            channelsEnabled: $channelsEnabled,
            map: $data['map'] ?? null,
            name: $data['name_zh_cn'] ?? null,
            page: $data['page'] ?? 1,
            perPage: $data['perPage'] ?? 20,
        ));

        return response()->json([...$result, 'success' => true]);
    }

    public function npcs(ListNpcsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->npcs->search(new NpcQuery(
            query: $data['query'] ?? null,
            map: $data['map'] ?? null,
            name: $data['name'] ?? null,
            displayName: $data['name_zh_cn'] ?? null,
            gameVisibleOnly: ($data['visibility'] ?? 'game') === 'game',
            page: $data['page'] ?? 1,
            perPage: $data['perPage'] ?? 20,
        ));

        return response()->json([...$result, 'success' => true]);
    }

    /**
     * Every NPC placed on one map. Map detail assembles a whole map, so this is
     * deliberately returned in full rather than paginated.
     */
    public function mapNpcs(ListNpcsRequest $request, string $map): JsonResponse
    {
        $rows = $this->npcs->onMap($map, ($request->validated()['visibility'] ?? 'game') === 'game');

        return response()->json(['data' => $rows, 'total' => count($rows), 'success' => true]);
    }

    public function mapImage(string $map): BinaryFileResponse
    {
        abort_unless(preg_match('/^[a-z0-9_@-]+$/', $map) === 1, 404);
        $path = $this->world->mapImagePath($map);
        abort_unless($path, 404);

        return response()->file($path, ['Cache-Control' => 'public, max-age=86400']);
    }

    public function npcImage(int $spriteId): BinaryFileResponse
    {
        $path = base_path("resources/game-data/world/npcs/{$spriteId}.png");
        abort_unless(is_file($path), 404);

        return response()->file($path, ['Cache-Control' => 'public, max-age=86400']);
    }
}
