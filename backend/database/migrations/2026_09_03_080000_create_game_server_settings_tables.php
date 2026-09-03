<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_server_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('server_key', 64);
            $table->string('setting_key', 128);
            $table->unsignedBigInteger('desired_value');
            $table->unsignedBigInteger('actual_value')->nullable();
            $table->string('status', 32);
            $table->unsignedBigInteger('revision_id')->nullable();
            $table->timestamps();
            $table->unique(['server_key', 'setting_key'], 'gss_server_setting_unique');
            $table->index(['status', 'updated_at'], 'gss_status_updated_idx');
        });

        Schema::create('game_server_setting_revisions', function (Blueprint $table): void {
            $table->id();
            $table->string('server_key', 64);
            $table->unsignedInteger('revision');
            $table->json('changes');
            $table->string('status', 32);
            $table->string('reason', 255);
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
            $table->unique(['server_key', 'revision'], 'gssr_server_revision_unique');
            $table->index(['server_key', 'created_at'], 'gssr_server_created_idx');
            $table->index(['status', 'created_at'], 'gssr_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_server_setting_revisions');
        Schema::dropIfExists('game_server_settings');
    }
};
