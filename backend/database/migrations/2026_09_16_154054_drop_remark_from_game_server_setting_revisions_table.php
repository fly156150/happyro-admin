<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_server_setting_revisions', function (Blueprint $table): void {
            $table->dropColumn('remark');
        });
    }

    public function down(): void
    {
        // Restores the column only; deleted remarks cannot be recovered.
        Schema::table('game_server_setting_revisions', function (Blueprint $table): void {
            $table->string('remark', 255)->nullable();
        });
    }
};
