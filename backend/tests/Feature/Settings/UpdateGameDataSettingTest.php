<?php

namespace Tests\Feature\Settings;

use App\Models\GameDataCatalog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UpdateGameDataSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_read_and_update_global_versions(): void
    {
        $this->catalogs('client2', 'server2');
        $this->actingAs($this->superAdmin());

        $this->getJson('/api/settings/game-data')->assertOk()
            ->assertJsonPath('data.available.client.0', 'client2')
            ->assertJsonPath('data.available.server.0', 'server2');
        $this->putJson('/api/settings/game-data', [
            'clientVersion' => 'client2', 'serverVersion' => 'server2',
        ])->assertOk()->assertJsonPath('data.serverVersion', 'server2');

        $this->assertDatabaseHas('game_data_settings', [
            'id' => 1, 'client_version' => 'client2', 'server_version' => 'server2',
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'settings.game_data_updated']);
    }

    public function test_update_rejects_server_version_missing_from_monster_catalog(): void
    {
        GameDataCatalog::factory()->create([
            'resource_type' => 'items', 'source' => 'client', 'ruleset' => 'client', 'source_version' => 'client2',
        ]);
        GameDataCatalog::factory()->create([
            'resource_type' => 'items', 'source' => 'server', 'ruleset' => 'renewal', 'source_version' => 'server2',
        ]);

        $this->actingAs($this->superAdmin())->putJson('/api/settings/game-data', [
            'clientVersion' => 'client2', 'serverVersion' => 'server2',
        ])->assertUnprocessable()->assertJsonValidationErrors('serverVersion');
    }

    public function test_setting_endpoints_require_manage_permission(): void
    {
        $this->getJson('/api/settings/game-data')->assertUnauthorized();
        $role = Role::query()->create(['name' => 'viewer', 'label' => '查看者']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->actingAs($user)->getJson('/api/settings/game-data')->assertForbidden();
        $this->putJson('/api/settings/game-data', [])->assertForbidden();
    }

    private function catalogs(string $clientVersion, string $serverVersion): void
    {
        GameDataCatalog::factory()->create([
            'resource_type' => 'items', 'source' => 'client', 'ruleset' => 'client', 'source_version' => $clientVersion,
        ]);
        foreach (['items', 'monsters'] as $resourceType) {
            GameDataCatalog::factory()->create([
                'resource_type' => $resourceType, 'source' => 'server',
                'ruleset' => 'renewal', 'source_version' => $serverVersion,
            ]);
        }
    }

    private function superAdmin(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'super_admin'], ['label' => '超级管理员']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
