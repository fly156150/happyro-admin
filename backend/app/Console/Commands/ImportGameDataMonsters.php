<?php

namespace App\Console\Commands;

use App\Services\GameData\ImportMonstersService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('game-data:import-monsters {--renewal : 导入 Renewal 魔物快照} {--no-color : 禁用 ANSI 颜色}')]
#[Description('Import the versioned monster snapshot into the HappyRO Admin database')]
class ImportGameDataMonsters extends Command
{
    public function __construct(private readonly ImportMonstersService $monsters)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->option('renewal')) {
            $this->renderUsage();

            return self::SUCCESS;
        }
        $result = $this->monsters->import((string) config('happyro.game_data.monster_snapshot'));
        $this->line(sprintf('renewal %s: imported=%d deleted=%d', $result['version'], $result['imported'], $result['deleted']));

        return self::SUCCESS;
    }

    private function renderUsage(): void
    {
        $color = ! $this->option('no-color');
        $style = fn (string $code, string $text): string => $color ? "\033[{$code}m{$text}\033[0m" : $text;
        $this->output->writeln('');
        $this->output->writeln($style('1;36', 'HappyRO 魔物资料导入'));
        $this->output->writeln('');
        $this->output->writeln($style('1;33', '用法'));
        $this->output->writeln('  '.$style('1;32', 'php artisan game-data:import-monsters').' --renewal');
        $this->output->writeln('');
        $this->output->writeln($style('1;33', '常用例子'));
        $this->output->writeln($style('36', '  php artisan game-data:import-monsters --renewal'));
        $this->output->writeln('');
    }
}
