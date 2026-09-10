<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_server_setting_revisions', function ($table): void {
            $table->renameColumn('reason', 'remark');
        });
        DB::statement('ALTER TABLE game_server_setting_revisions MODIFY remark VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE game_server_setting_revisions SET remark = '' WHERE remark IS NULL");
        DB::statement('ALTER TABLE game_server_setting_revisions MODIFY remark VARCHAR(255) NOT NULL');
        Schema::table('game_server_setting_revisions', function ($table): void {
            $table->renameColumn('remark', 'reason');
        });
    }
};
