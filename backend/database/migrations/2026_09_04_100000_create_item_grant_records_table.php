<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_grant_records', function (Blueprint $table): void {
            $table->id();
            $table->string('idempotency_key', 64)->unique();
            $table->string('request_hash', 64);
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('char_id');
            $table->unsignedInteger('amount');
            $table->string('title', 45);
            $table->string('status', 32);
            $table->unsignedBigInteger('mail_id')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index(['char_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_grant_records');
    }
};
