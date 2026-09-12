<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_server_setting_revisions', function ($table): void {
            $table->renameColumn('reason', 'remark');
        });
        $this->changeRemarkNullability(nullable: true);
    }

    public function down(): void
    {
        DB::table('game_server_setting_revisions')->whereNull('remark')->update(['remark' => '']);
        $this->changeRemarkNullability(nullable: false);
        Schema::table('game_server_setting_revisions', function ($table): void {
            $table->renameColumn('remark', 'reason');
        });
    }

    private function changeRemarkNullability(bool $nullable): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $nullSql = $nullable ? 'NULL' : 'NOT NULL';
            DB::statement("ALTER TABLE game_server_setting_revisions MODIFY remark VARCHAR(255) {$nullSql}");

            return;
        }

        Schema::table('game_server_setting_revisions', function ($table) use ($nullable): void {
            $column = $table->string('remark', 255);
            if ($nullable) {
                $column->nullable();
            }
            $column->change();
        });
    }
};
