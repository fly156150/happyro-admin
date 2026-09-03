<?php

namespace App\Console\Commands;

use App\Services\GameData\ImportItemsService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('game-data:import-items
    {--all : 导入客户端和 Renewal 服务端快照}
    {--client : 导入客户端快照}
    {--renewal : 导入 Renewal 服务端快照}
    {--no-color : 禁用 ANSI 颜色}')]
#[Description('Import versioned item snapshots into the HappyRO Admin database')]
class ImportGameDataItems extends Command
{
    public function __construct(private readonly ImportItemsService $items)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $selected = array_filter([
            'client' => $this->option('all') || $this->option('client'),
            'renewal' => $this->option('all') || $this->option('renewal'),
        ]);
        if ($selected === []) {
            $this->renderUsage();

            return self::SUCCESS;
        }

        $paths = array_map(
            fn (string $source): string => (string) config("happyro.game_data.item_snapshots.{$source}"),
            array_keys($selected),
        );
        foreach ($this->items->importMany($paths) as $result) {
            $this->line(sprintf(
                '%s/%s %s: imported=%d deleted=%d views=%d',
                $result['source'],
                $result['ruleset'],
                $result['version'],
                $result['imported'],
                $result['deleted'],
                $result['views'],
            ));
        }

        return self::SUCCESS;
    }

    private function renderUsage(): void
    {
        $color = ! $this->option('no-color');
        $style = fn (string $code, string $text): string => $color ? "\033[{$code}m{$text}\033[0m" : $text;

        $this->output->writeln('');
        $this->output->writeln($style('1;36', 'HappyRO 游戏资料导入'));
        $this->output->writeln('');
        $this->output->writeln($style('1;33', '用法'));
        $this->output->writeln('  '.$style('1;32', 'php artisan game-data:import-items').' [--all|--client|--renewal]');
        $this->output->writeln('');
        $this->output->writeln($style('1;33', '常用例子'));
        $this->output->writeln($style('36', '  php artisan game-data:import-items --all'));
        $this->output->writeln($style('36', '  php artisan game-data:import-items --client --renewal --no-color'));
        $this->output->writeln('');
    }
}
