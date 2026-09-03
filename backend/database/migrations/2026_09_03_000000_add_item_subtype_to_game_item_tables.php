<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_item_sources', function (Blueprint $table): void {
            $table->string('item_subtype', 32)->nullable()->after('item_type');
        });
        Schema::table('game_item_views', function (Blueprint $table): void {
            $table->string('item_subtype', 32)->nullable()->after('item_type');
            $table->index(
                ['client_catalog_id', 'server_catalog_id', 'item_type', 'item_subtype', 'item_id'],
                'game_item_view_subtype_filter_index',
            );
        });

        $this->backfillSubtype('game_item_sources');
        $this->backfillSubtype('game_item_views');
    }

    public function down(): void
    {
        Schema::table('game_item_views', function (Blueprint $table): void {
            $table->dropIndex('game_item_view_subtype_filter_index');
            $table->dropColumn('item_subtype');
        });
        Schema::table('game_item_sources', function (Blueprint $table): void {
            $table->dropColumn('item_subtype');
        });
    }

    private function backfillSubtype(string $table): void
    {
        DB::table($table)
            ->select(['id', 'payload'])
            ->whereNotNull('payload')
            ->chunkById(500, function ($records) use ($table): void {
                foreach ($records as $record) {
                    $payload = json_decode($record->payload, true);
                    $subtype = $payload['SubType'] ?? null;
                    if (is_string($subtype)) {
                        DB::table($table)->where('id', $record->id)->update(['item_subtype' => $subtype]);
                    }
                }
            });
    }
};
