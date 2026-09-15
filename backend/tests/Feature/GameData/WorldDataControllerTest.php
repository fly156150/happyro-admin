<?php

namespace Tests\Feature\GameData;

use App\Contracts\GameServer\GameServerGateway;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

final class WorldDataControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_game_scope_filters_unsupported_maps_and_hidden_channels(): void
    {
        $this->mock(GameServerGateway::class)->shouldReceive('battleConfig')->andReturn(['navigation_map_channels_enabled' => 0]);

        $response = $this->actingAs($this->superAdmin())->getJson('/api/game-data/maps?perPage=100');

        $response->assertOk();
        $this->assertNotContains(false, array_column($response->json('data'), 'supported'));
        $this->assertLessThan($response->json('total'), count($response->json('data')) + 1);
    }

    public function test_map_search_is_applied_before_pagination(): void
    {
        $this->mock(GameServerGateway::class)->shouldReceive('battleConfig')->andReturn(['navigation_map_channels_enabled' => 0]);

        $response = $this->actingAs($this->superAdmin())->getJson('/api/game-data/maps?map=moc_para01');

        $response->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.image_kind', 'terrain');
    }

    public function test_all_scope_keeps_server_entries_without_contacting_game_server(): void
    {
        $this->mock(GameServerGateway::class)->shouldNotReceive('battleConfig');

        $response = $this->actingAs($this->superAdmin())->getJson('/api/game-data/maps?scope=all&perPage=100&page=6');

        $response->assertOk();
        $this->assertContains(false, array_column($response->json('data'), 'supported'));
    }

    public function test_maps_are_paginated(): void
    {
        $this->mock(GameServerGateway::class)->shouldReceive('battleConfig')->andReturn(['navigation_map_channels_enabled' => 0]);

        $response = $this->actingAs($this->superAdmin())->getJson('/api/game-data/maps?perPage=5');

        $response->assertOk()->assertJsonCount(5, 'data');
        $this->assertGreaterThan(5, $response->json('total'));
        $this->assertSame('prontera', $response->json('data.0.map'));
    }

    public function test_terrain_preview_is_available_for_eden(): void
    {
        $response = $this->actingAs($this->superAdmin())->get('/api/game-data/maps/moc_para01/image');
        $response->assertOk();
        $this->assertStringStartsWith('image/png', (string) $response->headers->get('content-type'));
    }

    public function test_authenticated_operator_can_load_real_map_image(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get('/api/game-data/maps/prontera/image');

        $response->assertOk();
        $this->assertStringStartsWith('image/png', (string) $response->headers->get('content-type'));
    }

    public function test_authenticated_operator_can_load_instance_map_images(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get('/api/game-data/maps/1@nyd/image');

        $response->assertOk();
        $this->assertStringStartsWith('image/png', (string) $response->headers->get('content-type'));
    }

    private function superAdmin(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'super_admin'], ['label' => '超级管理员']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
