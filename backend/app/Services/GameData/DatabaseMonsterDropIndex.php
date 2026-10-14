<?php

namespace App\Services\GameData;

use Illuminate\Database\DatabaseManager;

final readonly class DatabaseMonsterDropIndex
{
    public function __construct(private DatabaseManager $database) {}

    public function rebuild(int $catalogId): void
    {
        $this->database->transaction(function () use ($catalogId): void {
            $this->database->table('game_monster_drops')->where('game_data_catalog_id', $catalogId)->delete();
            $this->database->table('game_monsters')->where('game_data_catalog_id', $catalogId)
                ->select(['id', 'payload'])->orderBy('id')->chunkById(200, function ($monsters) use ($catalogId): void {
                    $rows = [];
                    foreach ($monsters as $monster) {
                        $payload = json_decode($monster->payload, true, flags: JSON_THROW_ON_ERROR);
                        foreach (['Drops' => 'normal', 'MvpDrops' => 'mvp_reward'] as $field => $method) {
                            foreach ($payload[$field] ?? [] as $slot => $drop) {
                                if (empty($drop['Item']) || (int) ($drop['Rate'] ?? 0) <= 0) {
                                    continue;
                                }
                                $rows[] = [
                                    'game_data_catalog_id' => $catalogId, 'game_monster_id' => $monster->id,
                                    'item_aegis_name' => $drop['Item'], 'method' => $method,
                                    'slot' => $slot, 'rate' => (int) $drop['Rate'],
                                ];
                            }
                        }
                    }
                    foreach (array_chunk($rows, 200) as $chunk) {
                        $this->database->table('game_monster_drops')->insert($chunk);
                    }
                });
        });
    }
}
