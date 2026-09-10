<?php

namespace App\Infrastructure\GameServer;

use App\Contracts\GameServer\GameServerGateway;
use App\Data\GameServer\GameServerCapabilities;
use App\Data\GameServer\GameServerCommand;
use App\Data\GameServer\GameServerCommandResult;
use App\Exceptions\GameServerGatewayException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;

final class HttpGameServerGateway implements GameServerGateway
{
    public function __construct(
        private readonly Factory $http,
        private readonly string $baseUrl,
        private readonly string $token,
        private readonly int $connectTimeout,
        private readonly int $timeout,
    ) {}

    public function capabilities(): GameServerCapabilities
    {
        try {
            $response = $this->request()->get('/game-control/v1/capabilities');
        } catch (ConnectionException $exception) {
            throw new GameServerGatewayException('unavailable', 'Game server is unavailable.', previous: $exception);
        }

        $this->ensureSuccessful($response);
        $protocolVersion = $response->json('data.protocol_version');
        $commands = $response->json('data.commands');

        if (! is_string($protocolVersion) || ! is_array($commands) || array_filter($commands, fn (mixed $command): bool => ! is_string($command)) !== []) {
            throw new GameServerGatewayException('invalid_response', 'Game server returned an invalid response.');
        }

        return new GameServerCapabilities($protocolVersion, array_values($commands));
    }

    public function execute(GameServerCommand $command): GameServerCommandResult
    {
        try {
            $response = $this->request()
                ->withHeader('Idempotency-Key', $command->idempotencyKey)
                ->post('/game-control/v1/commands', [
                    'id' => $command->id,
                    'type' => $command->type->value,
                    'target' => $command->targetType === null ? null : [
                        'type' => $command->targetType,
                        'id' => $command->targetId,
                    ],
                    // PHP encodes an empty array as JSON [], while the Game
                    // Control envelope requires an object payload ({}).
                    'payload' => $command->payload === [] ? (object) [] : $command->payload,
                ]);
        } catch (ConnectionException $exception) {
            throw new GameServerGatewayException('unavailable', 'Game server is unavailable.', true, $exception);
        }

        $this->ensureSuccessful($response, true);
        $result = $response->json('data.result');

        if (! is_array($result)) {
            throw new GameServerGatewayException('invalid_response', 'Game server returned an invalid response.', true);
        }

        return new GameServerCommandResult($result);
    }

    /** @return array<string, int> */
    public function battleConfig(): array
    {
        try {
            $response = $this->request()->get('/game-control/v1/battle-config');
        } catch (ConnectionException $exception) {
            throw new GameServerGatewayException('unavailable', 'Game server is unavailable.', previous: $exception);
        }

        $this->ensureSuccessful($response);
        $values = $response->json('data.result.values');
        if (! is_array($values) || array_filter($values, fn (mixed $value): bool => ! is_int($value)) !== []) {
            throw new GameServerGatewayException('invalid_response', 'Game server returned an invalid response.');
        }

        return $values;
    }

    public function characterSnapshot(int $characterId): array
    {
        try {
            $response = $this->request()->post('/game-control/v1/commands', [
                'id' => (string) Str::uuid(),
                'type' => 'character.snapshot',
                'target' => ['type' => 'character', 'id' => (string) $characterId],
                'payload' => (object) [],
            ]);
        } catch (ConnectionException $exception) {
            throw new GameServerGatewayException('unavailable', 'Game server is unavailable.', previous: $exception);
        }

        $this->ensureSuccessful($response);
        $snapshot = $response->json('data.result');
        if (! is_array($snapshot) || ($snapshot['char_id'] ?? null) !== $characterId) {
            throw new GameServerGatewayException('invalid_response', 'Game server returned an invalid response.');
        }

        return $snapshot;
    }

    private function request(): PendingRequest
    {
        return $this->http
            ->baseUrl(rtrim($this->baseUrl, '/'))
            ->acceptJson()
            ->asJson()
            ->withToken($this->token)
            ->connectTimeout($this->connectTimeout)
            ->timeout($this->timeout);
    }

    private function ensureSuccessful(Response $response, bool $mayHaveSideEffects = false): void
    {
        if ($response->successful()) {
            return;
        }

        [$code, $message] = match ($response->status()) {
            401, 403 => ['authentication_failed', 'Game server authentication failed.'],
            404, 501 => ['unsupported_command', 'Game server does not support this command.'],
            409, 422 => $this->rejection($response),
            default => ['request_failed', 'Game server request failed.'],
        };

        $outcomeUnknown = $mayHaveSideEffects && ! in_array($response->status(), [401, 403, 404, 409, 422, 501], true);

        throw new GameServerGatewayException($code, $message, $outcomeUnknown);
    }

    /** @return array{string, string} */
    private function rejection(Response $response): array
    {
        $code = $response->json('error.code');
        $message = $response->json('error.message', 'Game server rejected the request.');

        if (! is_string($code) || preg_match('/^[a-z][a-z0-9_]{0,63}$/', $code) !== 1 || ! is_string($message) || mb_strlen($message) > 255) {
            return ['request_rejected', 'Game server rejected the request.'];
        }

        return [$code, $message];
    }
}
