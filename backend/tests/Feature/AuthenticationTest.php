<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_active_user_can_log_in_and_read_their_identity(): void
    {
        $role = Role::query()->create(['name' => 'super_admin', 'label' => '超级管理员']);
        $user = User::factory()->create([
            'username' => 'admin',
            'password' => 'correct-password',
        ]);
        $user->roles()->attach($role);

        $this->postJson('/api/auth/login', [
            'username' => 'ADMIN',
            'password' => 'correct-password',
            'remember' => true,
        ])->assertOk()->assertExactJson(['status' => 'ok']);

        $this->assertAuthenticatedAs($user);
        $this->getJson('/api/auth/user')->assertOk()->assertJsonPath('data.username', 'admin')
            ->assertJsonPath('data.roles.0', 'super_admin')
            ->assertJsonPath('data.permissions.0', '*');
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'event' => 'auth.login_succeeded',
        ]);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_invalid_credentials_are_rejected_and_audited(): void
    {
        User::factory()->create(['username' => 'admin']);

        $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonPath('message', '用户名或密码错误。');

        $this->assertGuest('web');
        $this->getJson('/api/auth/user')->assertUnauthorized();
        $this->assertDatabaseHas('audit_logs', [
            'username' => 'admin',
            'event' => 'auth.login_failed',
        ]);
    }

    public function test_a_disabled_user_cannot_log_in(): void
    {
        User::factory()->create([
            'username' => 'disabled',
            'password' => 'correct-password',
            'is_active' => false,
        ]);

        $this->postJson('/api/auth/login', [
            'username' => 'disabled',
            'password' => 'correct-password',
        ])->assertUnprocessable()->assertJsonPath('message', '用户名或密码错误。');

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failures(): void
    {
        $payload = ['username' => 'missing', 'password' => 'wrong-password'];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/auth/login', $payload)->assertUnprocessable();
        }

        $this->postJson('/api/auth/login', $payload)->assertTooManyRequests()
            ->assertHeader('Retry-After');
    }

    public function test_authenticated_user_can_log_out_and_invalidate_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/auth/logout')->assertNoContent();

        $this->assertGuest('web');
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'event' => 'auth.logout',
        ]);
    }

    public function test_unauthenticated_user_cannot_read_an_identity(): void
    {
        $this->getJson('/api/auth/user')->assertUnauthorized();
    }

    public function test_permissions_are_inherited_through_roles(): void
    {
        $role = Role::query()->create(['name' => 'operator', 'label' => '运营人员']);
        $permission = Permission::query()->create(['name' => 'players.view', 'label' => '查看玩家']);
        $role->permissions()->attach($permission);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->assertTrue($user->hasPermission('players.view'));
        $this->assertFalse($user->hasPermission('players.ban'));
    }
}
