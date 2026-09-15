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
        Schema::create('game_npcs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_data_catalog_id')->constrained()->cascadeOnDelete();
            $table->string('npc_key', 96);
            $table->string('map', 64);
            $table->string('map_name_zh_cn')->nullable();
            $table->unsignedSmallInteger('x');
            $table->unsignedSmallInteger('y');
            $table->string('name');
            $table->string('source_name');
            $table->string('display_name');
            $table->string('type', 32);
            $table->unsignedInteger('sprite_id')->nullable();
            $table->unsignedInteger('display_sprite_id')->nullable();
            $table->boolean('image_available')->default(false);
            $table->boolean('enabled')->default(true);
            $table->boolean('game_visible')->default(false);
            $table->unsignedInteger('catalog_order')->default(0);
            $table->json('payload');
            $table->uuid('sync_token');
            $table->timestamps();
            $table->unique(['game_data_catalog_id', 'npc_key']);
            $table->index(['game_data_catalog_id', 'map', 'catalog_order']);
            $table->index(['game_data_catalog_id', 'game_visible', 'catalog_order']);
            $table->index(['game_data_catalog_id', 'catalog_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_npcs');
    }
};
