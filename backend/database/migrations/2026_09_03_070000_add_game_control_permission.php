<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('permissions')->insertOrIgnore([
            ['name' => 'operations.game-control', 'label' => '游戏运行控制', 'created_at' => $now, 'updated_at' => $now],
        ]);
        $permissionId = DB::table('permissions')->where('name', 'operations.game-control')->value('id');
        $sourceId = DB::table('permissions')->where('name', 'operations.item-grant')->value('id');
        if ($permissionId && $sourceId) {
            DB::table('permission_role')->where('permission_id', $sourceId)->get()->each(function (object $assignment) use ($permissionId): void {
                DB::table('permission_role')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $assignment->role_id]);
            });
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'operations.game-control')->value('id');
        if ($permissionId) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
