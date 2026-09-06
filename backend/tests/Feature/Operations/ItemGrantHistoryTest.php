<?php

namespace Tests\Feature\Operations;

use App\Models\ItemGrantRecord;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

final class ItemGrantHistoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authorized_admin_can_query_item_grant_records(): void
    {
        $user = $this->superAdmin();
        ItemGrantRecord::query()->create(['idempotency_key' => 'history-1', 'request_hash' => str_repeat('a', 64), 'item_id' => 501, 'char_id' => 3, 'amount' => 10, 'title' => 'Gift', 'status' => 'sent', 'mail_id' => 42, 'requested_by' => $user->id]);
        $this->actingAs($user)->getJson('/api/operations/item-grants')->assertOk()->assertJsonPath('data.0.mail_id', 42)->assertJsonPath('data.0.requester.id', $user->id)->assertJsonPath('meta.total', 1);
    }

    private function superAdmin(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'super_admin'], ['label' => '超级管理员']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
