<?php

namespace Tests\Feature\GameServer;

use App\Contracts\GameServer\GameServerSettingRepository;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

final class DatabaseGameServerSettingRepositoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sync_applied_is_idempotent_per_server_and_key(): void
    {
        $repository = $this->app->make(GameServerSettingRepository::class);
        $repository->syncApplied('primary', ['base_exp_rate' => 100], 1);
        $repository->syncApplied('primary', ['base_exp_rate' => 200], 2);

        $this->assertDatabaseCount('game_server_settings', 1);
        $this->assertDatabaseHas('game_server_settings', [
            'server_key' => 'primary',
            'setting_key' => 'base_exp_rate',
            'desired_value' => 200,
            'actual_value' => 200,
            'revision_id' => 2,
            'status' => 'applied',
        ]);
        $this->assertSame(['base_exp_rate' => 200], $repository->current('primary'));
    }
}
