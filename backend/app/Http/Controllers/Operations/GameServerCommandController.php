<?php

namespace App\Http\Controllers\Operations;

use App\Contracts\GameServer\GameServerCommandRepository;
use App\Contracts\GameServer\GameServerGateway;
use App\Data\GameServer\GameServerCommandRequest as CommandRequest;
use App\Data\GameServer\GameServerCommandType;
use App\Exceptions\GameServerGatewayException;
use App\Http\Requests\Operations\SubmitGameServerCommandRequest;
use App\Services\GameServer\ExecuteGameServerCommandService;
use App\Services\GameServer\SubmitGameServerCommandService;
use App\Support\GameServerErrorMessage;
use Illuminate\Http\JsonResponse;

final class GameServerCommandController
{
    public function __construct(
        private readonly GameServerGateway $gateway,
        private readonly GameServerCommandRepository $commands,
        private readonly SubmitGameServerCommandService $submit,
        private readonly ExecuteGameServerCommandService $execute,
    ) {}

    public function capabilities(): JsonResponse
    {
        $capabilities = $this->gateway->capabilities();

        return response()->json(['data' => [
            'protocol_version' => $capabilities->protocolVersion,
            'commands' => $capabilities->commands,
        ], 'success' => true]);
    }

    public function battleConfig(): JsonResponse
    {
        return response()->json(['data' => ['values' => $this->gateway->battleConfig()], 'success' => true]);
    }

    public function store(SubmitGameServerCommandRequest $request): JsonResponse
    {
        $data = $request->validated();
        $target = $data['target'] ?? [];
        $submission = $this->submit->submit(new CommandRequest(
            $data['idempotency_key'],
            GameServerCommandType::from($data['type']),
            $target['type'] ?? null,
            isset($target['id']) ? (string) $target['id'] : null,
            $data['payload'] ?? [],
        ), $request->user());

        try {
            $command = $this->execute->complete($submission);
        } catch (GameServerGatewayException $exception) {
            $status = in_array($exception->errorCode, ['character_offline', 'no_spawn_cell'], true) ? 409 : 502;

            return response()->json(['error' => ['code' => $exception->errorCode, 'message' => GameServerErrorMessage::from($exception)]], $status);
        }

        return response()->json(['data' => $command, 'success' => true], $submission->created ? 202 : 200);
    }

    public function show(string $commandId): JsonResponse
    {
        $command = $this->commands->find($commandId);

        abort_if($command === null, 404);

        return response()->json(['data' => $command, 'success' => true]);
    }
}
