<?php

namespace Tests\Feature\GameData;

use App\Contracts\GameData\ItemViewBuilder;
use Mockery;
use Tests\TestCase;

final class RebuildItemViewsCommandTest extends TestCase
{
    public function test_command_without_run_flag_only_displays_usage(): void
    {
        $views = Mockery::mock(ItemViewBuilder::class);
        $views->expects('rebuildAll')->never();
        $this->app->instance(ItemViewBuilder::class, $views);

        $this->artisan('game-data:rebuild-item-views', ['--no-color' => true])
            ->expectsOutputToContain('HappyRO 物品查询视图重建')
            ->expectsOutputToContain('php artisan game-data:rebuild-item-views --run')
            ->assertSuccessful();
    }
}
