<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('game_data_items');

        Schema::create('game_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id')->unique();
            $table->timestamps();
        });

        Schema::create('game_item_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_data_catalog_id')->constrained()->cascadeOnDelete();
            $table->string('name_zh_cn');
            $table->string('name_en_us');
            $table->string('aegis_name')->nullable();
            $table->string('item_type', 32)->nullable();
            $table->string('resource_name')->nullable();
            $table->json('description')->nullable();
            $table->json('payload');
            $table->uuid('sync_token');
            $table->timestamps();
            $table->unique(['game_data_catalog_id', 'game_item_id'], 'game_item_source_identity_unique');
        });

        Schema::create('game_item_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('item_id');
            $table->foreignId('client_catalog_id')->constrained('game_data_catalogs')->cascadeOnDelete();
            $table->foreignId('server_catalog_id')->constrained('game_data_catalogs')->cascadeOnDelete();
            $table->boolean('client_exists');
            $table->boolean('server_exists');
            $table->string('name_zh_cn');
            $table->string('name_en_us');
            $table->string('aegis_name')->nullable();
            $table->string('item_type', 32)->nullable();
            $table->string('resource_name')->nullable();
            $table->json('description')->nullable();
            $table->unsignedInteger('buy')->nullable();
            $table->unsignedInteger('sell')->nullable();
            $table->unsignedInteger('weight')->nullable();
            $table->unsignedInteger('attack')->nullable();
            $table->unsignedInteger('defense')->nullable();
            $table->unsignedSmallInteger('slots')->nullable();
            $table->text('script')->nullable();
            $table->json('field_sources');
            $table->json('payload');
            $table->timestamps();
            $table->unique(
                ['client_catalog_id', 'server_catalog_id', 'item_id'],
                'game_item_view_identity_unique',
            );
            $table->index(
                ['client_catalog_id', 'server_catalog_id', 'item_type', 'item_id'],
                'game_item_view_filter_index',
            );
            $table->index(
                ['client_catalog_id', 'server_catalog_id', 'client_exists', 'item_id'],
                'game_item_view_client_range_index',
            );
            $table->index(
                ['client_catalog_id', 'server_catalog_id', 'server_exists', 'item_id'],
                'game_item_view_server_range_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_item_views');
        Schema::dropIfExists('game_item_sources');
        Schema::dropIfExists('game_items');

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
};
