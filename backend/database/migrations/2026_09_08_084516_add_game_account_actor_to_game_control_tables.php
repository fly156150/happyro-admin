<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_server_commands', function (Blueprint $table): void {
            $table->unsignedInteger('requested_game_account_id')->nullable()->after('requested_by');
            $table->index(['requested_game_account_id', 'created_at'], 'gsc_game_account_created_idx');
        });
        Schema::table('game_server_setting_revisions', function (Blueprint $table): void {
            $table->unsignedInteger('requested_game_account_id')->nullable()->after('requested_by');
            $table->index(['requested_game_account_id', 'created_at'], 'gssr_game_account_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('game_server_commands', function (Blueprint $table): void {
            $table->dropIndex('gsc_game_account_created_idx');
            $table->dropColumn('requested_game_account_id');
        });
        Schema::table('game_server_setting_revisions', function (Blueprint $table): void {
            $table->dropIndex('gssr_game_account_created_idx');
            $table->dropColumn('requested_game_account_id');
        });
    }
};
