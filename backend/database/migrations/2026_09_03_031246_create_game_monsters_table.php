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
        Schema::create('game_monsters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_data_catalog_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('monster_id');
            $table->string('aegis_name');
            $table->string('name_zh_cn');
            $table->string('name_en_us');
            $table->unsignedSmallInteger('level')->default(1);
            $table->unsignedBigInteger('hp')->default(1);
            $table->string('size', 32)->default('Small');
            $table->string('race', 32)->default('Formless');
            $table->string('element', 32)->default('Neutral');
            $table->unsignedTinyInteger('element_level')->default(1);
            $table->boolean('is_boss')->default(false);
            $table->string('sprite_name')->nullable();
            $table->json('payload');
            $table->uuid('sync_token');
            $table->timestamps();
            $table->unique(['game_data_catalog_id', 'monster_id']);
            $table->index(['game_data_catalog_id', 'race', 'monster_id']);
            $table->index(['game_data_catalog_id', 'element', 'monster_id']);
            $table->index(['game_data_catalog_id', 'size', 'monster_id']);
            $table->index(['game_data_catalog_id', 'level', 'monster_id']);
            $table->index(['game_data_catalog_id', 'is_boss', 'monster_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_monsters');
    }
};
