<?php

namespace Tests\Feature\GameServer;

use App\Contracts\GameServer\GameServerCommandRepository;
use App\Data\GameServer\GameServerCommandRequest;
use App\Data\GameServer\GameServerCommandStatus;
use App\Data\GameServer\GameServerCommandType;
use App\Exceptions\GameServerCommandStateException;
use App\Exceptions\IdempotencyConflictException;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

final class DatabaseGameServerCommandRepositoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_identical_submission_reuses_command(): void
    {
        $operator = User::factory()->create();
        $request = $this->request(['monster_id' => 1002, 'count' => 1]);
        $repository = $this->app->make(GameServerCommandRepository::class);

        $first = $repository->submit($request, $operator->id);
        $second = $repository->submit($request, $operator->id);

        $this->assertTrue($first->created);
        $this->assertFalse($second->created);
        $this->assertSame($first->command->id, $second->command->id);
        $this->assertSame(GameServerCommandStatus::Pending, $first->command->status);
        $this->assertDatabaseCount('game_server_commands', 1);
    }

    public function test_reused_key_with_different_request_is_rejected(): void
    {
        $operator = User::factory()->create();
        $repository = $this->app->make(GameServerCommandRepository::class);
        $repository->submit($this->request(['monster_id' => 1002]), $operator->id);

        $this->expectException(IdempotencyConflictException::class);

        $repository->submit($this->request(['monster_id' => 1003]), $operator->id);
    }

    public function test_command_follows_terminal_success_transition(): void
    {
        $repository = $this->app->make(GameServerCommandRepository::class);
        $submitted = $repository->submit($this->request(['monster_id' => 1002]), null);

        $running = $repository->markRunning($submitted->command->id);
        $succeeded = $repository->markSucceeded($running->id, ['spawned' => 1]);

        $this->assertSame(GameServerCommandStatus::Running, $running->status);
        $this->assertNotNull($running->startedAt);
        $this->assertSame(GameServerCommandStatus::Succeeded, $succeeded->status);
        $this->assertSame(['spawned' => 1], $succeeded->result);
        $this->assertNotNull($succeeded->completedAt);

        $this->expectException(GameServerCommandStateException::class);
        $repository->markRunning($succeeded->id);
    }

    public function test_running_command_can_finish_with_failure_details(): void
    {
        $repository = $this->app->make(GameServerCommandRepository::class);
        $submitted = $repository->submit($this->request(['monster_id' => 1002]), null);
        $repository->markRunning($submitted->command->id);

        $failed = $repository->markFailed($submitted->command->id, 'target_offline', 'Target character is offline.');

        $this->assertSame(GameServerCommandStatus::Failed, $failed->status);
        $this->assertSame('target_offline', $failed->errorCode);
        $this->assertSame('Target character is offline.', $failed->errorMessage);
        $this->assertNotNull($failed->completedAt);
    }

    public function test_indeterminate_command_can_be_retried_with_same_identity(): void
    {
        $repository = $this->app->make(GameServerCommandRepository::class);
        $submitted = $repository->submit($this->request(['monster_id' => 1002]), null);
        $repository->markRunning($submitted->command->id);

        $indeterminate = $repository->markIndeterminate($submitted->command->id, 'unavailable', 'Game server is unavailable.');
        $retried = $repository->markRunning($submitted->command->id);

        $this->assertSame(GameServerCommandStatus::Indeterminate, $indeterminate->status);
        $this->assertSame(GameServerCommandStatus::Running, $retried->status);
        $this->assertNull($retried->errorCode);
        $this->assertNull($retried->completedAt);
    }

    private function request(array $payload): GameServerCommandRequest
    {
        return new GameServerCommandRequest(
            'spawn-request-1',
            GameServerCommandType::MonsterSpawn,
            'character',
            '42',
            $payload,
        );
    }
}
