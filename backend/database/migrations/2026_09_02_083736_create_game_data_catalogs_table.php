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
        Schema::create('game_data_catalogs', function (Blueprint $table) {
            $table->id();
            $table->string('resource_type', 32);
            $table->string('source', 16);
            $table->string('ruleset', 32);
            $table->string('source_version', 64);
            $table->string('content_hash', 64);
            $table->unsignedInteger('record_count');
            $table->json('source_metadata');
            $table->timestamp('imported_at');
            $table->timestamps();
            $table->unique(
                ['resource_type', 'source', 'ruleset', 'source_version'],
                'game_data_catalog_identity_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_data_catalogs');
    }
};
