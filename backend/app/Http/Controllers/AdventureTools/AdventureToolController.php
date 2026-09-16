<?php

namespace App\Http\Controllers\AdventureTools;

use App\Contracts\GameServer\GameServerGateway;
use App\Data\Auth\GameSessionPrincipal;
use App\Data\GameServer\GameServerCommandRequest;
use App\Data\GameServer\GameServerCommandType;
use App\Exceptions\GameServerGatewayException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdventureTools\ApplyGameSettingsRequest;
use App\Http\Requests\AdventureTools\RunCharacterMaintenanceRequest;
use App\Services\AdventureTools\AdventureToolAccessService;
use App\Services\GameServer\ApplyGameServerSettingsService;
use App\Services\GameServer\ExecuteGameServerCommandService;
use App\Services\GameServer\GameServerSettingRegistry;
use App\Services\GameServer\SubmitGameServerCommandService;
use App\Support\GameServerErrorMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdventureToolController extends Controller
{
    public function __construct(
        private GameServerGateway $gateway,
        private GameServerSettingRegistry $registry,
        private SubmitGameServerCommandService $submit,
        private ExecuteGameServerCommandService $execute,
        private ApplyGameServerSettingsService $applySettings,
        private AdventureToolAccessService $access,
    ) {}

    public function bootstrap(Request $request): JsonResponse
    {
        $principal = $this->principal($request);
        try {
            $values = $this->gateway->battleConfig();
        } catch (GameServerGatewayException $exception) {
            return $this->gatewayError($exception);
        }

        return response()->json(['data' => [
            'administrator' => $principal->administrator,
            'characterMaintenanceAllowed' => $this->access->allowsPolicy($values['game_tools_character_maintenance_policy'] ?? 2, $principal),
            'gameSettingsAllowed' => $this->access->allowsPolicy($values['game_tools_game_settings_policy'] ?? 2, $principal),
            'itemGrantAllowed' => $this->access->allowsPolicy($values['game_tools_item_grant_policy'] ?? 2, $principal),
        ]]);
    }

    public function character(Request $request): JsonResponse
    {
        $principal = $this->principal($request);
        try {
            $this->access->authorize('game_tools_character_maintenance_policy', $principal);

            return response()->json(['data' => $this->gateway->characterSnapshot($principal->characterId)]);
        } catch (GameServerGatewayException $exception) {
            return $this->gatewayError($exception);
        }
    }

    public function maintain(RunCharacterMaintenanceRequest $request): JsonResponse
    {
        $principal = $this->principal($request);
        try {
            $this->access->authorize('game_tools_character_maintenance_policy', $principal);
            $data = $request->validated();
            $submission = $this->submit->submitForGameAccount(new GameServerCommandRequest(
                $data['idempotency_key'],
                GameServerCommandType::from($data['type']),
                'character',
                (string) $principal->characterId,
                $data['payload'] ?? [],
            ), $principal->accountId);
            $this->execute->complete($submission);

            return response()->json(['data' => $this->gateway->characterSnapshot($principal->characterId)]);
        } catch (GameServerGatewayException $exception) {
            return $this->gatewayError($exception);
        }
    }

    public function gameSettings(Request $request): JsonResponse
    {
        $principal = $this->principal($request);
        try {
            $values = $this->gateway->battleConfig();
        } catch (GameServerGatewayException $exception) {
            return $this->gatewayError($exception);
        }
        abort_unless($this->access->allowsPolicy($values['game_tools_game_settings_policy'] ?? 2, $principal), 403);

        $definitions = [];
        foreach ($this->registry->adventureToolDefinitions() as $definition) {
            $definitions[$definition->key] = [
                'key' => $definition->key,
                'minimum' => $definition->minimum,
                'maximum' => $definition->maximum,
                'unit' => $definition->unit,
            ];
        }

        return response()->json(['data' => [
            'values' => array_intersect_key($values, $definitions),
            'definitions' => $definitions,
        ]]);
    }

    public function applyGameSettings(ApplyGameSettingsRequest $request): JsonResponse
    {
        $principal = $this->principal($request);
        try {
            $this->access->authorize('game_tools_game_settings_policy', $principal);
            $data = $request->validated();
            $this->applySettings->applyForGameAccount($data['changes'], $principal->accountId);

            return response()->json(['data' => ['values' => $this->gateway->battleConfig()]]);
        } catch (GameServerGatewayException $exception) {
            return $this->gatewayError($exception);
        }
    }

    private function principal(Request $request): GameSessionPrincipal
    {
        return $request->attributes->get('game_session');
    }

    private function gatewayError(GameServerGatewayException $exception): JsonResponse
    {
        $status = match (true) {
            in_array($exception->errorCode, ['character_offline', 'reset_failed'], true) => 409,
            in_array($exception->errorCode, ['unavailable', 'map_server_unavailable'], true) => 503,
            default => 502,
        };

        return response()->json(['error' => ['code' => $exception->errorCode, 'message' => GameServerErrorMessage::from($exception)]], $status);
    }
}
