<?php

namespace Tests\Unit\GameServer;

use App\Infrastructure\GameServer\FileGameServerConfigWriter;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class FileGameServerConfigWriterTest extends TestCase
{
    public function test_replaces_only_the_managed_block(): void
    {
        $path = storage_path('framework/testing/battle_conf.txt');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, "base_exp_rate: 100\n// HAPPYRO ADMIN MANAGED START\nbase_exp_rate: 50\n// HAPPYRO ADMIN MANAGED END\nother: keep\n");

        (new FileGameServerConfigWriter($path))->write(['base_exp_rate' => 200]);

        $this->assertSame("base_exp_rate: 100\n// HAPPYRO ADMIN MANAGED START\nbase_exp_rate: 200\n// HAPPYRO ADMIN MANAGED END\nother: keep\n", File::get($path));
        File::delete($path);
    }

    public function test_preserves_unmodified_managed_settings(): void
    {
        $path = storage_path('framework/testing/battle_conf.txt');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, "// HAPPYRO ADMIN MANAGED START\nbase_exp_rate: 50\njob_exp_rate: 60\n// HAPPYRO ADMIN MANAGED END\n");

        (new FileGameServerConfigWriter($path))->write(['base_exp_rate' => 200]);

        $this->assertStringContainsString('job_exp_rate: 60', File::get($path));
        File::delete($path);
    }

    public function test_restores_a_snapshot_atomically(): void
    {
        $path = storage_path('framework/testing/battle_conf.txt');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, "original\n");
        $writer = new FileGameServerConfigWriter($path);
        $snapshot = $writer->snapshot();
        File::put($path, "changed\n");

        $writer->restore($snapshot);

        $this->assertSame("original\n", File::get($path));
        File::delete($path);
    }
}
