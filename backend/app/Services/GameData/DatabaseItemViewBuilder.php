<?php

namespace App\Services\GameData;

use App\Contracts\GameData\ItemViewBuilder;
use App\Models\GameDataCatalog;
use App\Models\GameItemSource;
use App\Models\GameItemView;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;

final class DatabaseItemViewBuilder implements ItemViewBuilder
{
    public function __construct(private readonly DatabaseManager $database) {}

    public function rebuildAll(): array
    {
        $clients = $this->catalogs('client', 'client');
        $servers = $this->catalogs('server', 'renewal');
        $records = 0;

        foreach ($clients as $client) {
            foreach ($servers as $server) {
                $records += $this->rebuild($client, $server);
            }
        }

        return ['combinations' => $clients->count() * $servers->count(), 'records' => $records];
    }

    private function catalogs(string $source, string $ruleset): Collection
    {
        return GameDataCatalog::query()
            ->where('resource_type', 'items')
            ->where('source', $source)
            ->where('ruleset', $ruleset)
            ->get();
    }

    private function rebuild(GameDataCatalog $client, GameDataCatalog $server): int
    {
        $clientItems = $this->sources($client->id);
        $serverItems = $this->sources($server->id);
        $itemIds = $clientItems->keys()->merge($serverItems->keys())->unique()->sort()->values();

        $this->database->transaction(function () use ($client, $server, $clientItems, $serverItems, $itemIds): void {
            GameItemView::query()
                ->where('client_catalog_id', $client->id)
                ->where('server_catalog_id', $server->id)
                ->delete();

            $now = now();
            foreach ($itemIds->chunk(500) as $chunk) {
                $rows = $chunk->map(fn (int $itemId): array => $this->row(
                    $client->id,
                    $server->id,
                    $clientItems->get($itemId),
                    $serverItems->get($itemId),
                    $now,
                ))->all();
                GameItemView::query()->insert($rows);
            }
        });

        return $itemIds->count();
    }

    /** @return Collection<int, GameItemSource> */
    private function sources(int $catalogId): Collection
    {
        return GameItemSource::query()
            ->with('item:id,item_id')
            ->where('game_data_catalog_id', $catalogId)
            ->get()
            ->keyBy(fn (GameItemSource $source): int => $source->item->item_id);
    }

    private function row(
        int $clientCatalogId,
        int $serverCatalogId,
        ?GameItemSource $client,
        ?GameItemSource $server,
        mixed $now,
    ): array {
        $primary = $client ?? $server;
        $serverPayload = $server?->payload ?? [];

        return [
            'game_item_id' => $primary->game_item_id,
            'item_id' => $primary->item->item_id,
            'client_catalog_id' => $clientCatalogId,
            'server_catalog_id' => $serverCatalogId,
            'client_exists' => $client !== null,
            'server_exists' => $server !== null,
            'name_zh_cn' => $client?->name_zh_cn ?? $server?->name_zh_cn ?? '',
            'name_en_us' => $client?->name_en_us ?? $server?->name_en_us ?? '',
            'aegis_name' => $server?->aegis_name,
            'item_type' => $server?->item_type,
            'item_subtype' => $server?->item_subtype,
            'resource_name' => $client?->resource_name,
            'description' => $this->json($client?->description),
            'buy' => $this->integer($serverPayload['Buy'] ?? null),
            'sell' => $this->integer($serverPayload['Sell'] ?? null),
            'weight' => $this->integer($serverPayload['Weight'] ?? null),
            'attack' => $this->integer($serverPayload['Attack'] ?? null),
            'defense' => $this->integer($serverPayload['Defense'] ?? null),
            'slots' => $this->integer($serverPayload['Slots'] ?? null),
            'script' => is_string($serverPayload['Script'] ?? null) ? $serverPayload['Script'] : null,
            'field_sources' => $this->json($this->fieldSources($client, $server, $serverPayload)),
            'payload' => $this->json([...$serverPayload, ...($client?->payload ?? [])]),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function fieldSources(?GameItemSource $client, ?GameItemSource $server, array $serverPayload): array
    {
        $sources = [
            'names' => $client ? 'client' : 'server',
            'description' => $client?->description ? 'client' : null,
            'resourceName' => $client?->resource_name ? 'client' : null,
            'AegisName' => $server?->aegis_name ? 'server' : null,
            'Type' => $server?->item_type ? 'server' : null,
            'SubType' => $server?->item_subtype ? 'server' : null,
        ];
        foreach (['Buy', 'Sell', 'Weight', 'Attack', 'Defense', 'Slots', 'Script'] as $field) {
            $sources[$field] = array_key_exists($field, $serverPayload) ? 'server' : null;
        }

        return array_filter($sources);
    }

    private function integer(mixed $value): ?int
    {
        return is_int($value) && $value >= 0 ? $value : null;
    }

    private function json(mixed $value): ?string
    {
        return $value === null ? null : json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
