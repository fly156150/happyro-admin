<?php

namespace App\Console\Commands;

use App\Services\GameData\ImportNpcsService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('game-data:import-npcs {--renewal : 导入 Renewal NPC 目录} {--no-color : 禁用 ANSI 颜色}')]
#[Description('Import the generated NPC catalog into the HappyRO Admin database')]
class ImportGameDataNpcs extends Command
{
    public function __construct(private readonly ImportNpcsService $npcs)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->option('renewal')) {
            $this->renderUsage();

            return self::SUCCESS;
        }
        $result = $this->npcs->import((string) config('happyro.game_data.npc_catalog_path'));
        $this->line(sprintf('renewal %s: imported=%d deleted=%d', $result['version'], $result['imported'], $result['deleted']));

        return self::SUCCESS;
    }

    private function renderUsage(): void
    {
        $color = ! $this->option('no-color');
        $style = fn (string $code, string $text): string => $color ? "\033[{$code}m{$text}\033[0m" : $text;
        $this->output->writeln('');
        $this->output->writeln($style('1;36', 'HappyRO NPC 目录导入'));
        $this->output->writeln('');
        $this->output->writeln($style('1;33', '用法'));
        $this->output->writeln('  '.$style('1;32', 'php artisan game-data:import-npcs').' --renewal');
        $this->output->writeln('');
        $this->output->writeln($style('1;33', '常用例子'));
        $this->output->writeln($style('36', '  php artisan game-data:import-npcs --renewal'));
        $this->output->writeln('');
    }
}
