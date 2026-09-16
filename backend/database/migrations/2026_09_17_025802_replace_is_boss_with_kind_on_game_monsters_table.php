<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_monsters', function (Blueprint $table): void {
            $table->string('kind', 16)->default('normal')->after('element_level');
        });
        DB::table('game_monsters')->orderBy('id')->chunkById(500, function ($rows): void {
            foreach ($rows as $row) {
                $payload = is_array($row->payload) ? $row->payload : json_decode((string) $row->payload, true);
                $kind = ! empty($payload['MvpDrops'] ?? null) ? 'mvp' : ($row->is_boss ? 'mini' : 'normal');
                DB::table('game_monsters')->where('id', $row->id)->update(['kind' => $kind]);
            }
        });
        Schema::table('game_monsters', function (Blueprint $table): void {
            $table->index(['game_data_catalog_id', 'kind', 'monster_id']);
            $table->dropIndex(['game_data_catalog_id', 'is_boss', 'monster_id']);
            $table->dropColumn('is_boss');
        });
    }

    public function down(): void
    {
        Schema::table('game_monsters', function (Blueprint $table): void {
            $table->boolean('is_boss')->default(false)->after('element_level');
        });
        DB::table('game_monsters')->orderBy('id')->chunkById(500, function ($rows): void {
            foreach ($rows as $row) {
                DB::table('game_monsters')->where('id', $row->id)->update([
                    'is_boss' => in_array($row->kind, ['mini', 'mvp'], true),
                ]);
            }
        });
        Schema::table('game_monsters', function (Blueprint $table): void {
            $table->index(['game_data_catalog_id', 'is_boss', 'monster_id']);
            $table->dropIndex(['game_data_catalog_id', 'kind', 'monster_id']);
            $table->dropColumn('kind');
        });
    }
};
