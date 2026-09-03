<?php

namespace Tests\Feature\GameData;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WorldDataControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_operator_can_load_real_map_image(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get('/api/game-data/maps/prontera/image');

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
