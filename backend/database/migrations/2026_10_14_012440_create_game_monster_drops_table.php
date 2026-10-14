<?php

use App\Services\GameData\DatabaseMonsterDropIndex;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_monster_drops', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_data_catalog_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_monster_id')->constrained()->cascadeOnDelete();
            $table->string('item_aegis_name');
            $table->string('method', 16);
            $table->unsignedSmallInteger('slot');
            $table->unsignedInteger('rate');
            $table->index(['game_data_catalog_id', 'item_aegis_name'], 'monster_drops_item_lookup');
            $table->unique(['game_monster_id', 'method', 'slot'], 'monster_drops_slot');
        });
        $index = app(DatabaseMonsterDropIndex::class);
        foreach (DB::table('game_data_catalogs')->where('resource_type', 'monsters')->pluck('id') as $id) {
            $index->rebuild($id);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('game_monster_drops');
    }
};
