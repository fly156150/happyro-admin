<?php

namespace App\Http\Controllers\Settings;

use App\Contracts\GameServer\GameServerGateway;
use App\Contracts\GameServer\GameServerSettingRevisionRepository;
use App\Exceptions\GameServerGatewayException;
use App\Http\Requests\Settings\ApplyGameServerSettingsRequest;
use App\Services\GameServer\ApplyGameServerSettingsService;
use App\Services\GameServer\GameServerSettingRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class GameServerSettingController
{
    public function __construct(
        private GameServerGateway $gateway,
        private GameServerSettingRevisionRepository $revisions,
        private GameServerSettingRegistry $registry,
        private ApplyGameServerSettingsService $applySettings,
    ) {}

    public function show(): JsonResponse
    {
        $definitions = [];
        foreach ($this->registry->definitions() as $definition) {
            $definitions[$definition->key] = [
                'key' => $definition->key,
                'minimum' => $definition->minimum,
                'maximum' => $definition->maximum,
                'source' => $definition->source,
                'unit' => $definition->unit,
            ];
        }

        try {
            $values = $this->gateway->battleConfig();
        } catch (GameServerGatewayException $exception) {
            $status = in_array($exception->errorCode, ['unavailable', 'map_server_unavailable'], true) ? 503 : 502;

            return response()->json(['message' => __('messages.game_server_unavailable')], $status);
        }

        return response()->json(['data' => ['values' => $values, 'definitions' => $definitions], 'success' => true]);
    }

    public function update(ApplyGameServerSettingsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $revision = $this->applySettings->apply(
            $data['changes'],
            $request->user(),
        );

        return response()->json([
            'data' => $revision,
            'success' => true,
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $revisions = $this->revisions->paginate(min(max($request->integer('per_page', 20), 1), 100));

        return response()->json(['data' => $revisions->items(), 'meta' => ['current_page' => $revisions->currentPage(), 'last_page' => $revisions->lastPage(), 'total' => $revisions->total()], 'success' => true]);
    }
}
