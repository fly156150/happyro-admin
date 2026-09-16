<?php

namespace App\Infrastructure\Persistence\GameServer;

use App\Contracts\GameServer\GameServerCommandRepository;
use App\Data\GameServer\GameServerCommand;
use App\Data\GameServer\GameServerCommandRequest;
use App\Data\GameServer\GameServerCommandStatus;
use App\Data\GameServer\GameServerCommandSubmission;
use App\Data\GameServer\OperationActor;
use App\Exceptions\GameServerCommandNotFoundException;
use App\Exceptions\GameServerCommandStateException;
use App\Exceptions\IdempotencyConflictException;
use App\Models\GameServerCommandRecord;
use Illuminate\Support\Str;

final class DatabaseGameServerCommandRepository implements GameServerCommandRepository
{
    public function submit(GameServerCommandRequest $request, OperationActor $actor): GameServerCommandSubmission
    {
        $requestHash = $request->fingerprint($actor);
        $record = GameServerCommandRecord::query()->firstOrCreate(
            ['idempotency_key' => $request->idempotencyKey],
            [
                'id' => (string) Str::uuid(),
                'request_hash' => $requestHash,
                'type' => $request->type,
                'status' => GameServerCommandStatus::Pending,
                'target_type' => $request->targetType,
                'target_id' => $request->targetId,
                'payload' => $request->payload,
                'requested_by' => $actor->adminUserId,
                'requested_game_account_id' => $actor->gameAccountId,
            ],
        );

        if (! hash_equals($record->request_hash, $requestHash)) {
            throw new IdempotencyConflictException($request->idempotencyKey);
        }

        return new GameServerCommandSubmission($this->toData($record), $record->wasRecentlyCreated);
    }

    public function find(string $id): ?GameServerCommand
    {
        $record = GameServerCommandRecord::query()->find($id);

        return $record ? $this->toData($record) : null;
    }

    public function markRunning(string $id): GameServerCommand
    {
        return $this->transition($id, [GameServerCommandStatus::Pending, GameServerCommandStatus::Failed, GameServerCommandStatus::Indeterminate], GameServerCommandStatus::Running, [
            'started_at' => now(),
            'completed_at' => null,
            'error_code' => null,
            'error_message' => null,
        ]);
    }

    public function markSucceeded(string $id, array $result): GameServerCommand
    {
        return $this->transition($id, [GameServerCommandStatus::Running, GameServerCommandStatus::Indeterminate], GameServerCommandStatus::Succeeded, [
            'result' => $result,
            'completed_at' => now(),
        ]);
    }

    public function markFailed(string $id, string $errorCode, string $errorMessage): GameServerCommand
    {
        return $this->transition($id, [GameServerCommandStatus::Running, GameServerCommandStatus::Indeterminate], GameServerCommandStatus::Failed, [
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    public function markIndeterminate(string $id, string $errorCode, string $errorMessage): GameServerCommand
    {
        return $this->transition($id, [GameServerCommandStatus::Running], GameServerCommandStatus::Indeterminate, [
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    /**
     * @param  list<GameServerCommandStatus>  $currentStatuses
     * @param  array<string, mixed>  $attributes
     */
    private function transition(
        string $id,
        array $currentStatuses,
        GameServerCommandStatus $next,
        array $attributes,
    ): GameServerCommand {
        foreach ($currentStatuses as $currentStatus) {
            if (! $currentStatus->canTransitionTo($next)) {
                throw new GameServerCommandStateException($id, $currentStatus, $next);
            }
        }

        $updated = GameServerCommandRecord::query()
            ->whereKey($id)
            ->whereIn('status', array_map(fn (GameServerCommandStatus $status): string => $status->value, $currentStatuses))
            ->update($attributes + ['status' => $next->value]);

        if ($updated === 0) {
            $record = GameServerCommandRecord::query()->find($id);
            if (! $record) {
                throw new GameServerCommandNotFoundException($id);
            }

            throw new GameServerCommandStateException($id, $record->status, $next);
        }

        return $this->toData(GameServerCommandRecord::query()->findOrFail($id));
    }

    private function toData(GameServerCommandRecord $record): GameServerCommand
    {
        return new GameServerCommand(
            id: $record->id,
            idempotencyKey: $record->idempotency_key,
            type: $record->type,
            status: $record->status,
            targetType: $record->target_type,
            targetId: $record->target_id,
            payload: $record->payload,
            result: $record->result,
            errorCode: $record->error_code,
            errorMessage: $record->error_message,
            requestedBy: $record->requested_by,
            requestedGameAccountId: $record->requested_game_account_id,
            startedAt: $record->started_at,
            completedAt: $record->completed_at,
            createdAt: $record->created_at,
            updatedAt: $record->updated_at,
        );
    }
}
