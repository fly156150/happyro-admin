<?php

namespace App\Console\Commands;

use App\Contracts\GameData\ItemViewBuilder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('game-data:rebuild-item-views
    {--run : 重建所有客户端与 Renewal 服务端版本组合}
    {--no-color : 禁用 ANSI 颜色}')]
#[Description('Rebuild merged item query views from imported source catalogs')]
class RebuildGameDataItemViews extends Command
{
    public function __construct(private readonly ItemViewBuilder $views)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->option('run')) {
            $this->renderUsage();

            return self::SUCCESS;
        }

        $result = $this->views->rebuildAll();
        $this->line(sprintf(
            'item views: combinations=%d records=%d',
            $result['combinations'],
            $result['records'],
        ));

        return self::SUCCESS;
    }

    private function renderUsage(): void
    {
        $color = ! $this->option('no-color');
        $style = fn (string $code, string $text): string => $color ? "\033[{$code}m{$text}\033[0m" : $text;

        $this->output->writeln('');
        $this->output->writeln($style('1;36', 'HappyRO 物品查询视图重建'));
        $this->output->writeln('');
        $this->output->writeln($style('1;33', '用法'));
        $this->output->writeln('  '.$style('1;32', 'php artisan game-data:rebuild-item-views').' --run');
        $this->output->writeln('');
        $this->output->writeln($style('1;33', '常用例子'));
        $this->output->writeln($style('36', '  php artisan game-data:rebuild-item-views --run'));
        $this->output->writeln($style('36', '  php artisan game-data:rebuild-item-views --run --no-color'));
        $this->output->writeln('');
    }
}
