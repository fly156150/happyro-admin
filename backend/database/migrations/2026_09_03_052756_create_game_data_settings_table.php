<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_data_settings', function (Blueprint $table) {
            $table->id();
            $table->string('client_version', 64);
            $table->string('server_version', 64);
            $table->timestamps();
        });

        DB::table('game_data_settings')->insert([
            'id' => 1,
            'client_version' => config('happyro.game_data.default_client_version'),
            'server_version' => config('happyro.game_data.default_server_version'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_data_settings');
    }
};
