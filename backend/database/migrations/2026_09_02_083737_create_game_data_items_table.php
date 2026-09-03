<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_data_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_data_catalog_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('item_id');
            $table->string('name_zh_cn');
            $table->string('name_en_us');
            $table->string('aegis_name')->nullable();
            $table->string('item_type', 32)->nullable();
            $table->string('resource_name')->nullable();
            $table->json('description')->nullable();
            $table->json('payload');
            $table->uuid('sync_token');
            $table->timestamps();
            $table->unique(['game_data_catalog_id', 'item_id'], 'game_data_item_identity_unique');
            $table->index(['game_data_catalog_id', 'item_type', 'item_id'], 'game_data_item_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_data_items');
    }
};
