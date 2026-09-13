<?php

namespace App\Services\GameData;

use App\Contracts\GameData\GameDataSettingRepository;
use App\Data\GameData\GameDataVersions;
use App\Models\GameDataCatalog;

final class DatabaseGameDataSettingRepository implements GameDataSettingRepository
{
    public function current(): GameDataVersions
    {
        return new GameDataVersions(
            config('happyro.game_data.default_client_version'),
            config('happyro.game_data.default_server_version'),
        );
    }

    public function available(): array
    {
        $client = GameDataCatalog::query()->where('resource_type', 'items')
            ->where('source', 'client')->where('ruleset', 'client')
            ->orderByDesc('imported_at')->pluck('source_version')->unique()->values()->all();
        $itemServer = $this->serverVersions('items');
        $monsterServer = $this->serverVersions('monsters');

        return ['client' => $client, 'server' => array_values(array_intersect($itemServer, $monsterServer))];
    }

    /** @return list<string> */
    private function serverVersions(string $resourceType): array
    {
        return GameDataCatalog::query()->where('resource_type', $resourceType)
            ->where('source', 'server')->where('ruleset', 'renewal')
            ->orderByDesc('imported_at')->pluck('source_version')->unique()->values()->all();
    }
}
