<?php

namespace App\Http\Controllers\AdventureTools;

use App\Contracts\GameData\ItemAssetRepository;
use App\Contracts\GameData\ItemRepository;
use App\Data\Auth\GameSessionPrincipal;
use App\Data\GameData\ItemQuery;
use App\Data\GameServer\OperationActor;
use App\Exceptions\GameServerGatewayException;
use App\Http\Requests\AdventureTools\GrantAdventureItemRequest;
use App\Http\Requests\AdventureTools\GrantAdventureZenyRequest;
use App\Http\Requests\AdventureTools\ListAdventureItemsRequest;
use App\Services\AdventureTools\AdventureToolAccessService;
use App\Services\AdventureTools\GrantAdventureItemService;
use App\Services\Operations\GrantCharacterZenyService;
use App\Support\GameServerErrorMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class AdventureItemController
{
    public function __construct(
        private readonly ItemRepository $items,
        private readonly ItemAssetRepository $assets,
        private readonly AdventureToolAccessService $access,
        private readonly GrantAdventureItemService $grantItems,
        private readonly GrantCharacterZenyService $grantZeny,
    ) {}

    public function index(ListAdventureItemsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->items->search(new ItemQuery(
            $data['query'] ?? null,
            $data['type'] ?? null,
            $data['subtype'] ?? null,
            'client',
            $data['page'] ?? 1,
            $data['perPage'] ?? 30,
        ));
        $result['data'] = array_map($this->withAssets(...), $result['data']);

        return response()->json($result);
    }

    public function grantZeny(GrantAdventureZenyRequest $request): JsonResponse
    {
        $principal = $this->principal($request);
        try {
            $this->access->authorize('game_tools_item_grant_policy', $principal);
            $data = $request->validated();
            $result = $this->grantZeny->grant(
                $data['idempotency_key'],
                $principal->characterId,
                $data['amount'],
                OperationActor::gameAccount($principal->accountId),
            );

            return response()->json(['data' => $result]);
        } catch (GameServerGatewayException $exception) {
            $status = in_array($exception->errorCode, ['character_offline', 'zeny_amount_exceeded', 'command_not_replayable'], true) ? 409 : 502;

            return response()->json(['error' => ['code' => $exception->errorCode, 'message' => GameServerErrorMessage::from($exception)]], $status);
        }
    }

    public function show(int $itemId): JsonResponse
    {
        $item = $this->items->find($itemId, 'client');
        abort_unless($item, 404);

        return response()->json(['data' => $this->withAssets($item)]);
    }

    public function icon(int $itemId): BinaryFileResponse
    {
        $path = $this->assets->iconPath($itemId);
        abort_unless($path, 404);

        return response()->file($path, ['Cache-Control' => 'private, max-age=86400']);
    }

    public function illustration(int $itemId): BinaryFileResponse
    {
        $path = $this->assets->illustrationPath($itemId);
        abort_unless($path, 404);

        return response()->file($path, ['Cache-Control' => 'private, max-age=86400']);
    }

    public function grant(GrantAdventureItemRequest $request): JsonResponse
    {
        $principal = $this->principal($request);

        try {
            $this->access->authorize('game_tools_item_grant_policy', $principal);

            return response()->json(['data' => $this->grantItems->grant($request->validated(), $principal)]);
        } catch (GameServerGatewayException $exception) {
            $conflicts = ['character_offline', 'inventory_full', 'inventory_overweight', 'item_amount_exceeded', 'command_not_replayable'];
            $invalidItems = ['invalid_parameter', 'item_not_found', 'item_not_grantable'];
            $status = match (true) {
                in_array($exception->errorCode, $conflicts, true) => 409,
                in_array($exception->errorCode, $invalidItems, true) => 422,
                in_array($exception->errorCode, ['unavailable', 'map_server_unavailable'], true) => 503,
                default => 502,
            };

            return response()->json(['error' => ['code' => $exception->errorCode, 'message' => GameServerErrorMessage::from($exception)]], $status);
        }
    }

    private function principal(Request $request): GameSessionPrincipal
    {
        return $request->attributes->get('game_session');
    }

    private function withAssets(array $item): array
    {
        $id = (int) $item['Id'];
        $icon = $this->assets->iconPath($id);
        $illustration = $this->assets->illustrationPath($id);
        $item['icon'] = $this->assetUrl($icon, "/api/adventure-tools/items/{$id}/icon");
        $item['illustration'] = $this->assetUrl($illustration, "/api/adventure-tools/items/{$id}/illustration");
        $item['grantable'] = ($item['Type'] ?? null) !== 'PetEgg';

        return $item;
    }

    private function assetUrl(?string $path, string $url): ?string
    {
        return $path === null ? null : $url.'?v='.filemtime($path);
    }
}
