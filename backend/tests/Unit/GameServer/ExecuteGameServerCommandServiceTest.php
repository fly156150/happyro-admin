<?php

namespace Tests\Unit\GameServer;

use App\Contracts\GameServer\GameServerCommandRepository;
use App\Contracts\GameServer\GameServerGateway;
use App\Data\GameServer\GameServerCommand;
use App\Data\GameServer\GameServerCommandResult;
use App\Data\GameServer\GameServerCommandStatus;
use App\Data\GameServer\GameServerCommandType;
use App\Exceptions\GameServerGatewayException;
use App\Services\GameServer\ExecuteGameServerCommandService;
use Carbon\CarbonImmutable;
use Mockery;
use RuntimeException;
use Tests\TestCase;

final class ExecuteGameServerCommandServiceTest extends TestCase
{
    public function test_execute_marks_command_succeeded(): void
    {
        $running = $this->command(GameServerCommandStatus::Running);
        $succeeded = $this->command(GameServerCommandStatus::Succeeded, ['spawned' => 1]);
        $commands = Mockery::mock(GameServerCommandRepository::class);
        $gateway = Mockery::mock(GameServerGateway::class);
        $commands->expects('markRunning')->with('command-1')->andReturn($running);
        $gateway->expects('execute')->with($running)->andReturn(new GameServerCommandResult(['spawned' => 1]));
        $commands->expects('markSucceeded')->with('command-1', ['spawned' => 1])->andReturn($succeeded);

        $result = (new ExecuteGameServerCommandService($commands, $gateway))->execute('command-1');

        $this->assertSame(GameServerCommandStatus::Succeeded, $result->status);
    }

    public function test_execute_records_gateway_failure_and_rethrows(): void
    {
        $running = $this->command(GameServerCommandStatus::Running);
        $commands = Mockery::mock(GameServerCommandRepository::class);
        $gateway = Mockery::mock(GameServerGateway::class);
        $commands->expects('markRunning')->with('command-1')->andReturn($running);
        $gateway->expects('execute')->with($running)->andThrow(new GameServerGatewayException('unavailable', 'Game server is unavailable.'));
        $commands->expects('markFailed')->with('command-1', 'unavailable', 'Game server is unavailable.')->andReturn($this->command(GameServerCommandStatus::Failed));

        $this->expectException(GameServerGatewayException::class);
        $this->expectExceptionMessage('Game server is unavailable.');

        (new ExecuteGameServerCommandService($commands, $gateway))->execute('command-1');
    }

    public function test_execute_does_not_persist_unexpected_exception_details(): void
    {
        $running = $this->command(GameServerCommandStatus::Running);
        $commands = Mockery::mock(GameServerCommandRepository::class);
        $gateway = Mockery::mock(GameServerGateway::class);
        $commands->expects('markRunning')->andReturn($running);
        $gateway->expects('execute')->andThrow(new RuntimeException('database password leaked'));
        $commands->expects('markIndeterminate')->with('command-1', 'unexpected_error', 'The game server command outcome could not be confirmed.')->andReturn($this->command(GameServerCommandStatus::Indeterminate));

        $this->expectException(RuntimeException::class);

        (new ExecuteGameServerCommandService($commands, $gateway))->execute('command-1');
    }

    public function test_execute_marks_uncertain_gateway_outcome_indeterminate(): void
    {
        $running = $this->command(GameServerCommandStatus::Running);
        $commands = Mockery::mock(GameServerCommandRepository::class);
        $gateway = Mockery::mock(GameServerGateway::class);
        $commands->expects('markRunning')->andReturn($running);
        $gateway->expects('execute')->andThrow(new GameServerGatewayException('unavailable', 'Game server is unavailable.', true));
        $commands->expects('markIndeterminate')->with('command-1', 'unavailable', 'Game server is unavailable.')->andReturn($this->command(GameServerCommandStatus::Indeterminate));

        $this->expectException(GameServerGatewayException::class);

        (new ExecuteGameServerCommandService($commands, $gateway))->execute('command-1');
    }

    /** @param array<string, mixed>|null $result */
    private function command(GameServerCommandStatus $status, ?array $result = null): GameServerCommand
    {
        $now = CarbonImmutable::now();

        return new GameServerCommand(
            id: 'command-1',
            idempotencyKey: 'request-1',
            type: GameServerCommandType::MonsterSpawn,
            status: $status,
            targetType: 'character',
            targetId: '42',
            payload: ['monster_id' => 1002],
            result: $result,
            errorCode: null,
            errorMessage: null,
            requestedBy: 7,
            startedAt: $status === GameServerCommandStatus::Pending ? null : $now,
            completedAt: in_array($status, [GameServerCommandStatus::Succeeded, GameServerCommandStatus::Failed, GameServerCommandStatus::Indeterminate], true) ? $now : null,
            createdAt: $now,
            updatedAt: $now,
        );
    }
}
