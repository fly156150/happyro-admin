<?php

namespace Tests\Unit\GameServer;

use App\Contracts\GameServer\GameServerCommandRepository;
use App\Data\GameServer\GameServerCommand;
use App\Data\GameServer\GameServerCommandRequest;
use App\Data\GameServer\GameServerCommandStatus;
use App\Data\GameServer\GameServerCommandSubmission;
use App\Data\GameServer\GameServerCommandType;
use App\Models\User;
use App\Services\GameServer\SubmitGameServerCommandService;
use Carbon\CarbonImmutable;
use Mockery;
use Tests\TestCase;

final class SubmitGameServerCommandServiceTest extends TestCase
{
    public function test_submit_passes_authenticated_operator_to_repository(): void
    {
        $request = new GameServerCommandRequest(
            'request-1',
            GameServerCommandType::MonsterSpawn,
            'character',
            '42',
            ['monster_id' => 1002],
        );
        $operator = new User(['username' => 'admin']);
        $operator->id = 7;
        $now = CarbonImmutable::now();
        $command = new GameServerCommand(
            id: 'command-1',
            idempotencyKey: 'request-1',
            type: GameServerCommandType::MonsterSpawn,
            status: GameServerCommandStatus::Pending,
            targetType: 'character',
            targetId: '42',
            payload: ['monster_id' => 1002],
            result: null,
            errorCode: null,
            errorMessage: null,
            requestedBy: 7,
            startedAt: null,
            completedAt: null,
            createdAt: $now,
            updatedAt: $now,
        );
        $submission = new GameServerCommandSubmission($command, true);
        $commands = Mockery::mock(GameServerCommandRepository::class);
        $commands->expects('submit')->with($request, 7)->andReturn($submission);

        $result = (new SubmitGameServerCommandService($commands))->submit($request, $operator);

        $this->assertSame($submission, $result);
    }
}
