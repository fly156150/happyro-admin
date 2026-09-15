<?php

namespace App\Http\Controllers\AdventureTools;

use App\Contracts\GameServer\GameServerGateway;
use App\Data\GameData\MapQuery;
use App\Data\GameData\NpcQuery;
use App\Exceptions\GameServerGatewayException;
use App\Http\Requests\AdventureTools\ListAdventureMapsRequest;
use App\Http\Requests\AdventureTools\ListAdventureNpcsRequest;
use App\Services\GameData\NpcCatalogService;
use App\Services\GameData\WorldDataService;
use Illuminate\Http\JsonResponse;

/**
 * Read-only world catalogs for the in-game adventure tools.
 *
 * Browsable lists are paginated; the per-map NPC list is not, because the map
 * preview has to place every marker on one map at once.
 */
final class AdventureWorldController
{
    public function __construct(
        private readonly WorldDataService $world,
        private readonly NpcCatalogService $npcs,
    ) {}

    public function npcs(ListAdventureNpcsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->npcs->search(new NpcQuery(
            query: $data['query'] ?? null,
            onMap: $data['onMap'] ?? null,
            currentMap: $data['currentMap'] ?? null,
            gameVisibleOnly: true,
            page: $data['page'] ?? 1,
            perPage: $data['perPage'] ?? 32,
        ));

        return response()->json($result);
    }

    public function mapNpcs(string $map): JsonResponse
    {
        $rows = $this->npcs->onMap($map, true);

        return response()->json(['data' => $rows, 'total' => count($rows)]);
    }

    public function maps(ListAdventureMapsRequest $request, GameServerGateway $gateway): JsonResponse
    {
        $data = $request->validated();
        try {
            $channelsEnabled = (bool) ($gateway->battleConfig()['navigation_map_channels_enabled'] ?? false);
        } catch (GameServerGatewayException $exception) {
            return response()->json(
                ['error' => ['code' => $exception->errorCode, 'message' => $exception->getMessage()]],
                503,
            );
        }

        return response()->json($this->world->maps(new MapQuery(
            gameOnly: true,
            adventureOrder: true,
            channelsEnabled: $channelsEnabled,
            query: $data['query'] ?? null,
            onMap: $data['onMap'] ?? null,
            currentMap: $data['currentMap'] ?? null,
            page: $data['page'] ?? 1,
            perPage: $data['perPage'] ?? 35,
        )));
    }
}
