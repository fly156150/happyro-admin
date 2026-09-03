<?php

namespace App\Infrastructure\Persistence\GameServer;

use App\Contracts\GameServer\GameServerSettingRepository;
use App\Models\GameServerSetting;
use Illuminate\Support\Facades\DB;

final class DatabaseGameServerSettingRepository implements GameServerSettingRepository
{
    public function syncApplied(string $serverKey, array $values, int $revisionId): void
    {
        DB::transaction(function () use ($serverKey, $values, $revisionId): void {
            foreach ($values as $key => $value) {
                GameServerSetting::query()->updateOrCreate(
                    ['server_key' => $serverKey, 'setting_key' => $key],
                    [
                        'desired_value' => $value,
                        'actual_value' => $value,
                        'status' => 'applied',
                        'revision_id' => $revisionId,
                    ],
                );
            }
        });
    }

    public function current(string $serverKey): array
    {
        return GameServerSetting::query()
            ->where('server_key', $serverKey)
            ->where('status', 'applied')
            ->pluck('actual_value', 'setting_key')
            ->map(static fn (mixed $value): int => (int) $value)
            ->all();
    }
}
