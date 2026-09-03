<?php

namespace App\Services\GameData;

use App\Contracts\GameData\GameDataSettingRepository;
use App\Data\GameData\GameDataVersions;
use App\Models\GameDataCatalog;
use App\Models\GameDataSetting;

final class DatabaseGameDataSettingRepository implements GameDataSettingRepository
{
    public function current(): GameDataVersions
    {
        $setting = GameDataSetting::query()->firstOrCreate(['id' => 1], [
            'client_version' => config('happyro.game_data.default_client_version'),
            'server_version' => config('happyro.game_data.default_server_version'),
        ]);

        return new GameDataVersions($setting->client_version, $setting->server_version);
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

    public function save(GameDataVersions $versions): GameDataVersions
    {
        GameDataSetting::query()->updateOrCreate(['id' => 1], [
            'client_version' => $versions->client,
            'server_version' => $versions->server,
        ]);

        return $versions;
    }

    /** @return list<string> */
    private function serverVersions(string $resourceType): array
    {
        return GameDataCatalog::query()->where('resource_type', $resourceType)
            ->where('source', 'server')->where('ruleset', 'renewal')
            ->orderByDesc('imported_at')->pluck('source_version')->unique()->values()->all();
    }
}
