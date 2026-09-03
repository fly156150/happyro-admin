<?php

namespace App\Http\Controllers\Settings;

use App\Contracts\GameServer\GameServerGateway;
use App\Http\Requests\Settings\ApplyGameServerSettingsRequest;
use App\Services\GameServer\ApplyGameServerSettingsService;
use App\Services\GameServer\GameServerSettingRegistry;
use Illuminate\Http\JsonResponse;

final readonly class GameServerSettingController
{
    public function __construct(
        private GameServerGateway $gateway,
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

        return response()->json([
            'data' => [
                'values' => $this->gateway->battleConfig(),
                'definitions' => $definitions,
            ],
            'success' => true,
        ]);
    }

    public function update(ApplyGameServerSettingsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $revision = $this->applySettings->apply(
            $data['changes'],
            $data['reason'],
            $request->user(),
        );

        return response()->json([
            'data' => $revision,
            'success' => true,
        ]);
    }
}
