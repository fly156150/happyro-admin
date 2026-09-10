<?php

namespace App\Infrastructure\Persistence\GameServer;

use App\Contracts\GameServer\GameServerSettingRevisionRepository;
use App\Data\GameServer\OperationActor;
use App\Models\GameServerSettingRevision;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class DatabaseGameServerSettingRevisionRepository implements GameServerSettingRevisionRepository
{
    public function create(string $serverKey, array $changes, ?string $remark, OperationActor $actor): GameServerSettingRevision
    {
        return DB::transaction(function () use ($serverKey, $changes, $remark, $actor): GameServerSettingRevision {
            $revision = (int) GameServerSettingRevision::query()
                ->where('server_key', $serverKey)
                ->lockForUpdate()
                ->max('revision') + 1;

            return GameServerSettingRevision::query()->create([
                'server_key' => $serverKey,
                'revision' => $revision,
                'changes' => $changes,
                'status' => 'draft',
                'remark' => $remark,
                'requested_by' => $actor->adminUserId,
                'requested_game_account_id' => $actor->gameAccountId,
            ]);
        });
    }

    public function find(string $serverKey, int $revision): ?GameServerSettingRevision
    {
        return GameServerSettingRevision::query()
            ->where('server_key', $serverKey)
            ->where('revision', $revision)
            ->first();
    }

    public function markApplied(int $id): GameServerSettingRevision
    {
        return $this->transition($id, 'applied', ['applied_at' => now()]);
    }

    public function markFailed(int $id): GameServerSettingRevision
    {
        return $this->transition($id, 'failed');
    }

    public function paginate(int $perPage): LengthAwarePaginator
    {
        return GameServerSettingRevision::query()
            ->with('requester:id,name,username')
            ->latest()
            ->paginate($perPage);
    }

    /** @param array<string, mixed> $attributes */
    private function transition(int $id, string $status, array $attributes = []): GameServerSettingRevision
    {
        $updated = GameServerSettingRevision::query()
            ->whereKey($id)
            ->where('status', 'draft')
            ->update(['status' => $status, ...$attributes]);

        if ($updated !== 1) {
            throw new \LogicException('Game server setting revision cannot transition from its current state.');
        }

        return GameServerSettingRevision::query()->findOrFail($id);
    }
}
