<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_running_without_arguments_only_displays_usage(): void
    {
        $this->artisan('gm:user:create', ['--no-color' => true])
            ->expectsOutputToContain('HappyRO GM 用户管理')
            ->expectsOutputToContain('php artisan gm:user:create <username> [选项]')
            ->assertSuccessful();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_it_creates_a_user_with_a_role_and_an_argon2id_password(): void
    {
        $this->artisan('gm:user:create', [
            'username' => 'admin',
            '--name' => '管理员',
            '--password' => 'a-secure-password',
            '--role' => 'super_admin',
            '--no-color' => true,
        ])->assertSuccessful();

        $user = User::query()->where('username', 'admin')->firstOrFail();
        $this->assertTrue($user->hasRole('super_admin'));
        $this->assertStringStartsWith('$argon2id$', $user->password);
        $this->assertDatabaseHas('audit_logs', ['event' => 'user.created']);
    }
}
