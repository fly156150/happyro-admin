<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_server_commands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('idempotency_key', 64)->unique();
            $table->char('request_hash', 64);
            $table->string('type', 64)->index();
            $table->string('status', 16);
            $table->string('target_type', 32)->nullable();
            $table->string('target_id', 64)->nullable();
            $table->json('payload');
            $table->json('result')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'gsc_status_created_idx');
            $table->index(['target_type', 'target_id', 'created_at'], 'gsc_target_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_server_commands');
    }
};
