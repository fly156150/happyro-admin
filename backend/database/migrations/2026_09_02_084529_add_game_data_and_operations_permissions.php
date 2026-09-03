<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();
        DB::table('permissions')->insertOrIgnore([
            ['name' => 'game-data.view', 'label' => '查看游戏资料', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'operations.item-grant', 'label' => '发放物品', 'created_at' => $now, 'updated_at' => $now],
        ]);
        $this->copyRoleAssignments('players.view', 'game-data.view');
        $this->copyRoleAssignments('players.edit', 'operations.item-grant');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['game-data.view', 'operations.item-grant'])
            ->pluck('id');
        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }

    private function copyRoleAssignments(string $from, string $to): void
    {
        $sourceId = DB::table('permissions')->where('name', $from)->value('id');
        $targetId = DB::table('permissions')->where('name', $to)->value('id');
        if (! $sourceId || ! $targetId) {
            return;
        }
        $rows = DB::table('permission_role')
            ->where('permission_id', $sourceId)
            ->pluck('role_id')
            ->map(fn (int $roleId): array => ['permission_id' => $targetId, 'role_id' => $roleId])
            ->all();
        if ($rows !== []) {
            DB::table('permission_role')->insertOrIgnore($rows);
        }
    }
};
