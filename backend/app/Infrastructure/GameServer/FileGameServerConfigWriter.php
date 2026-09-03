<?php

namespace App\Infrastructure\GameServer;

use App\Contracts\GameServer\GameServerConfigWriter;
use RuntimeException;

final class FileGameServerConfigWriter implements GameServerConfigWriter
{
    private const START = '// HAPPYRO ADMIN MANAGED START';

    private const END = '// HAPPYRO ADMIN MANAGED END';

    public function __construct(private readonly string $path) {}

    public function write(array $changes): void
    {
        $content = is_file($this->path) ? file_get_contents($this->path) : '';
        if ($content === false) {
            throw new RuntimeException('Unable to read game server configuration.');
        }

        $pattern = '/'.preg_quote(self::START, '/').'(.*?)'.preg_quote(self::END, '/').'/s';
        $managed = [];
        if (preg_match($pattern, $content, $match) === 1) {
            foreach (preg_split('/\R/', trim($match[1])) ?: [] as $line) {
                if (preg_match('/^([a-z0-9_]+):\s*(-?\d+)$/', trim($line), $entry) === 1) {
                    $managed[$entry[1]] = (int) $entry[2];
                }
            }
        }
        $managed = [...$managed, ...$changes];
        $block = self::START.PHP_EOL;
        foreach ($managed as $key => $value) {
            $block .= $key.': '.$value.PHP_EOL;
        }
        $block .= self::END.PHP_EOL;
        $pattern = '/'.preg_quote(self::START, '/').'.*?'.preg_quote(self::END, '/').'\\R?/s';
        $updated = preg_replace($pattern, $block, $content, 1, $count);
        if ($updated === null) {
            throw new RuntimeException('Unable to update game server configuration.');
        }
        if ($count === 0) {
            $updated = rtrim($content).PHP_EOL.PHP_EOL.$block;
        }

        $temporary = $this->path.'.tmp.'.bin2hex(random_bytes(8));
        if (file_put_contents($temporary, $updated, LOCK_EX) === false || ! rename($temporary, $this->path)) {
            @unlink($temporary);
            throw new RuntimeException('Unable to atomically replace game server configuration.');
        }
    }

    public function snapshot(): string
    {
        $content = is_file($this->path) ? file_get_contents($this->path) : '';

        if ($content === false) {
            throw new RuntimeException('Unable to read game server configuration.');
        }

        return $content;
    }

    public function restore(string $snapshot): void
    {
        $temporary = $this->path.'.tmp.'.bin2hex(random_bytes(8));
        if (file_put_contents($temporary, $snapshot, LOCK_EX) === false || ! rename($temporary, $this->path)) {
            @unlink($temporary);
            throw new RuntimeException('Unable to restore game server configuration.');
        }
    }
}
