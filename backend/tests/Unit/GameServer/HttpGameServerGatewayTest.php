<?php

namespace Tests\Unit\GameServer;

use App\Data\GameServer\GameServerCommand;
use App\Data\GameServer\GameServerCommandStatus;
use App\Data\GameServer\GameServerCommandType;
use App\Exceptions\GameServerGatewayException;
use App\Infrastructure\GameServer\HttpGameServerGateway;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Tests\TestCase;

final class HttpGameServerGatewayTest extends TestCase
{
    public function test_reads_authenticated_capabilities(): void
    {
        $http = $this->fakeHttp([
            'http://127.0.0.1:8889/game-control/v1/capabilities' => Factory::response([
                'data' => ['protocol_version' => '1', 'commands' => ['monster.spawn']],
            ]),
        ]);

        $capabilities = $this->gateway($http)->capabilities();

        $this->assertSame('1', $capabilities->protocolVersion);
        $this->assertTrue($capabilities->supports(GameServerCommandType::MonsterSpawn));
        $http->assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer test-token'));
    }

    public function test_sends_structured_command_with_idempotency_key(): void
    {
        $http = $this->fakeHttp([
            'http://127.0.0.1:8889/game-control/v1/commands' => Factory::response([
                'data' => ['result' => ['spawned' => 1]],
            ]),
        ]);

        $result = $this->gateway($http)->execute($this->command());

        $this->assertSame(['spawned' => 1], $result->data);
        $http->assertSent(function (Request $request): bool {
            return $request->hasHeader('Idempotency-Key', 'request-1')
                && $request['type'] === 'monster.spawn'
                && $request['target'] === ['type' => 'character', 'id' => '42']
                && $request['payload'] === ['monster_id' => 1002, 'count' => 1];
        });
    }

    public function test_reads_live_character_snapshot(): void
    {
        $http = $this->fakeHttp([
            'http://127.0.0.1:8889/game-control/v1/commands' => Factory::response([
                'data' => ['result' => ['char_id' => 150002, 'name' => '测试角色', 'str' => 10]],
            ]),
        ]);

        $snapshot = $this->gateway($http)->characterSnapshot(150002);

        $this->assertSame(150002, $snapshot['char_id']);
        $http->assertSent(fn (Request $request): bool => $request['type'] === 'character.snapshot'
            && $request['target'] === ['type' => 'character', 'id' => '150002']
            && (array) $request['payload'] === []);
    }

    public function test_maps_authentication_failure_without_remote_details(): void
    {
        $http = $this->fakeHttp([
            'http://127.0.0.1:8889/game-control/v1/capabilities' => Factory::response([
                'error' => ['code' => 'debug_secret', 'message' => 'Sensitive details'],
            ], 401),
        ]);

        try {
            $this->gateway($http)->capabilities();
            $this->fail('Expected a gateway exception.');
        } catch (GameServerGatewayException $exception) {
            $this->assertSame('authentication_failed', $exception->errorCode);
            $this->assertSame('Game server authentication failed.', $exception->getMessage());
        }
    }

    public function test_rejects_invalid_success_payload(): void
    {
        $http = $this->fakeHttp([
            'http://127.0.0.1:8889/game-control/v1/capabilities' => Factory::response([
                'data' => ['protocol_version' => 1, 'commands' => []],
            ]),
        ]);

        $this->expectException(GameServerGatewayException::class);
        $this->expectExceptionMessage('Game server returned an invalid response.');

        $this->gateway($http)->capabilities();
    }

    public function test_marks_server_error_after_write_as_unknown_outcome(): void
    {
        $http = $this->fakeHttp([
            'http://127.0.0.1:8889/game-control/v1/commands' => Factory::response([], 500),
        ]);

        try {
            $this->gateway($http)->execute($this->command());
            $this->fail('Expected a gateway exception.');
        } catch (GameServerGatewayException $exception) {
            $this->assertSame('request_failed', $exception->errorCode);
            $this->assertTrue($exception->outcomeUnknown);
        }
    }

    public function test_preserves_400_parameter_rejection_as_known_failure(): void
    {
        $http = $this->fakeHttp([
            'http://127.0.0.1:8889/game-control/v1/commands' => Factory::response([
                'error' => ['code' => 'invalid_parameter'],
            ], 400),
        ]);

        try {
            $this->gateway($http)->execute($this->command());
            $this->fail('Expected a gateway exception.');
        } catch (GameServerGatewayException $exception) {
            $this->assertSame('invalid_parameter', $exception->errorCode);
            $this->assertFalse($exception->outcomeUnknown);
        }
    }

    public function test_maps_not_implemented_command_to_explicit_rejection(): void
    {
        $http = $this->fakeHttp([
            'http://127.0.0.1:8889/game-control/v1/commands' => Factory::response([], 501),
        ]);

        try {
            $this->gateway($http)->execute($this->command());
            $this->fail('Expected a gateway exception.');
        } catch (GameServerGatewayException $exception) {
            $this->assertSame('unsupported_command', $exception->errorCode);
            $this->assertFalse($exception->outcomeUnknown);
        }
    }

    public function test_preserves_error_code_when_server_omits_message(): void
    {
        $http = $this->fakeHttp([
            'http://127.0.0.1:8889/game-control/v1/commands' => Factory::response(['error' => ['code' => 'character_offline']], 409),
        ]);

        try {
            $this->gateway($http)->execute($this->command());
            $this->fail('Expected a gateway exception.');
        } catch (GameServerGatewayException $exception) {
            $this->assertSame('character_offline', $exception->errorCode);
        }
    }

    /** @param array<string, mixed> $responses */
    private function fakeHttp(array $responses): Factory
    {
        $http = new Factory($this->app->make('events'));
        $http->preventStrayRequests();
        $http->fake($responses);

        return $http;
    }

    private function gateway(Factory $http): HttpGameServerGateway
    {
        return new HttpGameServerGateway($http, 'http://127.0.0.1:8889', 'test-token', 1, 3);
    }

    private function command(): GameServerCommand
    {
        $now = CarbonImmutable::parse('2026-09-03 12:00:00');

        return new GameServerCommand(
            id: 'command-1',
            idempotencyKey: 'request-1',
            type: GameServerCommandType::MonsterSpawn,
            status: GameServerCommandStatus::Running,
            targetType: 'character',
            targetId: '42',
            payload: ['monster_id' => 1002, 'count' => 1],
            result: null,
            errorCode: null,
            errorMessage: null,
            requestedBy: 7,
            startedAt: $now,
            completedAt: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }
}
