<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\Players\DatabaseLoginLogRepository;
use App\Services\Players\DatabasePlayerAccountRepository;
use App\Services\Players\DatabasePlayerCharacterRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class PlayerRepositoriesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['game', 'game_log'] as $connection) {
            config(["database.connections.{$connection}" => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
            DB::purge($connection);
        }
        $this->createTables();
    }

    public function test_account_filters_can_be_combined(): void
    {
        DB::connection('game')->table('login')->insert([
            ['account_id' => 1, 'userid' => 'alice', 'email' => 'alice@example.com', 'sex' => 'F', 'group_id' => 0, 'state' => 0],
            ['account_id' => 2, 'userid' => 'alex', 'email' => 'other@example.com', 'sex' => 'M', 'group_id' => 0, 'state' => 1],
        ]);
        $repository = new DatabasePlayerAccountRepository;
        $this->assertSame(1, $repository->paginate('ali', 'example.com', 'active', 20)->total());
        $this->assertSame(1, $repository->paginate('alex', null, 'banned', 20)->total());
    }

    public function test_character_and_login_log_filters_work(): void
    {
        DB::connection('game')->table('login')->insert(['account_id' => 1, 'userid' => 'alice', 'email' => 'alice@example.com', 'sex' => 'F', 'group_id' => 0, 'state' => 0]);
        DB::connection('game')->table('char')->insert(['char_id' => 10, 'account_id' => 1, 'name' => 'Poring', 'class' => 0, 'base_level' => 1, 'job_level' => 1, 'online' => 0]);
        DB::connection('game_log')->table('loginlog')->insert(['time' => '2026-09-01 00:00:00', 'ip' => '127.0.0.1', 'user' => 'alice', 'rcode' => 100, 'log' => 'login ok']);
        $this->assertSame(1, (new DatabasePlayerCharacterRepository)->paginate('ali', 20)->total());
        $this->assertSame(1, (new DatabaseLoginLogRepository)->paginate('ali', 20)->total());
    }

    public function test_http_queries_map_table_fields_to_repository_filters(): void
    {
        DB::connection('game')->table('login')->insert(['account_id' => 1, 'userid' => 'alice', 'email' => 'alice@example.com', 'sex' => 'F', 'group_id' => 0, 'state' => 0]);
        DB::connection('game')->table('char')->insert(['char_id' => 10, 'account_id' => 1, 'name' => 'Poring', 'class' => 0, 'base_level' => 1, 'job_level' => 1, 'online' => 0]);
        DB::connection('game_log')->table('loginlog')->insert(['time' => '2026-09-01 00:00:00', 'ip' => '127.0.0.1', 'user' => 'alice', 'rcode' => 100, 'log' => 'login ok']);
        $this->actingAs($this->superAdmin());
        $this->getJson('/api/players/accounts?username=ali&email=example.com&state=0&perPage=1')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('pageSize', 1);
        $this->getJson('/api/players/characters?username=ali')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.username', 'alice');
        $this->getJson('/api/players/login-logs?username=ali')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.username', 'alice');
    }

    public function test_player_queries_require_authentication(): void
    {
        $this->getJson('/api/players/accounts')->assertUnauthorized();
        $this->getJson('/api/players/characters')->assertUnauthorized();
        $this->getJson('/api/players/login-logs')->assertUnauthorized();
    }

    public function test_account_actions_change_player_data(): void
    {
        DB::connection('game')->table('login')->insert([
            ['account_id' => 1, 'userid' => 'alice', 'email' => 'alice@example.com', 'sex' => 'F', 'group_id' => 0, 'state' => 0],
            ['account_id' => 2, 'userid' => 'bob', 'email' => 'bob@example.com', 'sex' => 'M', 'group_id' => 0, 'state' => 0],
        ]);
        DB::connection('game')->table('char')->insert([
            ['char_id' => 10, 'account_id' => 1, 'name' => 'Alice', 'class' => 0, 'base_level' => 1, 'job_level' => 1, 'online' => 1],
            ['char_id' => 11, 'account_id' => 2, 'name' => 'Bob', 'class' => 0, 'base_level' => 1, 'job_level' => 1, 'online' => 0],
        ]);
        $this->actingAs($this->superAdmin());
        $this->postJson('/api/players/accounts/batch', ['action' => 'ban', 'account_ids' => [1, 2]])->assertOk()->assertJsonPath('count', 2);
        $this->assertSame(2, DB::connection('game')->table('login')->where('state', 1)->count());
        $this->postJson('/api/players/accounts/batch', ['action' => 'kick', 'account_ids' => [1]])->assertOk()->assertJsonPath('count', 1);
        $this->assertSame(0, DB::connection('game')->table('char')->value('online'));
        $this->deleteJson('/api/players/accounts/2')->assertOk();
        $this->assertDatabaseMissing('login', ['account_id' => 2], 'game');
        $this->assertDatabaseMissing('char', ['account_id' => 2], 'game');
        $this->assertDatabaseHas('char', ['account_id' => 1], 'game');
    }

    public function test_missing_account_actions_return_not_found(): void
    {
        $this->actingAs($this->superAdmin());

        $this->patchJson('/api/players/accounts/999', ['state' => 1])->assertNotFound();
        $this->deleteJson('/api/players/accounts/999')->assertNotFound();
    }

    private function superAdmin(): User
    {
        $role = Role::query()->create(['name' => 'super_admin', 'label' => 'Super administrator']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    private function createTables(): void
    {
        Schema::connection('game')->create('login', function (Blueprint $table): void {
            $table->integer('account_id')->primary();
            $table->string('userid');
            $table->string('email');
            $table->string('sex');
            $table->integer('group_id');
            $table->integer('state');
            $table->dateTime('lastlogin')->nullable();
            $table->string('last_ip')->nullable();
        });
        Schema::connection('game')->create('char', function (Blueprint $table): void {
            $table->integer('char_id')->primary();
            $table->integer('account_id');
            $table->string('name');
            $table->integer('class');
            $table->integer('base_level');
            $table->integer('job_level');
            $table->string('last_map')->nullable();
            $table->integer('online');
            $table->dateTime('last_login')->nullable();
        });
        Schema::connection('game_log')->create('loginlog', function (Blueprint $table): void {
            $table->dateTime('time');
            $table->string('ip');
            $table->string('user');
            $table->integer('rcode');
            $table->string('log');
        });
    }
}
