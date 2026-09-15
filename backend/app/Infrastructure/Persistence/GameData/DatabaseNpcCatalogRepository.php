<?php

namespace App\Infrastructure\Persistence\GameData;

use App\Contracts\GameData\NpcCatalogRepository;
use App\Data\GameData\NpcCatalogSnapshot;
use App\Models\GameDataCatalog;
use App\Models\GameNpc;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class DatabaseNpcCatalogRepository implements NpcCatalogRepository
{
    /** @var list<string> */
    private const COLUMNS = [
        'npc_key', 'map', 'map_name_zh_cn', 'x', 'y', 'name', 'source_name', 'display_name',
        'type', 'sprite_id', 'display_sprite_id', 'image_available', 'enabled', 'game_visible',
        'catalog_order', 'payload',
    ];

    public function __construct(private DatabaseManager $database) {}

    public function replace(NpcCatalogSnapshot $snapshot): array
    {
        return $this->database->transaction(fn (): array => $this->persist($snapshot));
    }

    /** @return array{version: string, imported: int, deleted: int} */
    private function persist(NpcCatalogSnapshot $snapshot): array
    {
        $catalog = GameDataCatalog::query()->firstOrCreate([
            'resource_type' => 'npcs', 'source' => 'server',
            'ruleset' => 'renewal', 'source_version' => $snapshot->version,
        ], [
            'content_hash' => $snapshot->contentHash, 'record_count' => 0,
            'source_metadata' => $snapshot->metadata, 'imported_at' => now(),
        ]);
        $token = (string) Str::uuid();
        $now = now();
        foreach (array_chunk($snapshot->npcs, 500) as $entries) {
            $rows = array_map(fn (array $entry): array => $this->row($catalog->id, $entry, $token, $now), $entries);
            GameNpc::query()->upsert($rows, ['game_data_catalog_id', 'npc_key'], [
                ...array_slice(self::COLUMNS, 1), 'sync_token', 'updated_at',
            ]);
        }
        $deleted = GameNpc::query()->where('game_data_catalog_id', $catalog->id)
            ->where('sync_token', '!=', $token)->delete();
        $catalog->update([
            'content_hash' => $snapshot->contentHash, 'record_count' => count($snapshot->npcs),
            'source_metadata' => $snapshot->metadata, 'imported_at' => $now,
        ]);

        return ['version' => $snapshot->version, 'imported' => count($snapshot->npcs), 'deleted' => $deleted];
    }

    /** @param array<string, mixed> $entry @return array<string, mixed> */
    private function row(int $catalogId, array $entry, string $token, mixed $now): array
    {
        $key = $entry['id'] ?? null;
        if (! is_string($key) || $key === '' || ! is_string($entry['map'] ?? null)) {
            throw new RuntimeException('Invalid NPC catalog entry: '.json_encode($key));
        }

        return [
            'game_data_catalog_id' => $catalogId,
            'npc_key' => $key,
            'map' => $entry['map'],
            'map_name_zh_cn' => is_string($entry['map_name_zh_cn'] ?? null) ? $entry['map_name_zh_cn'] : null,
            'x' => (int) ($entry['x'] ?? 0),
            'y' => (int) ($entry['y'] ?? 0),
            'name' => (string) ($entry['name'] ?? ''),
            'source_name' => (string) ($entry['source_name'] ?? ''),
            'display_name' => (string) ($entry['display_name'] ?? ''),
            'type' => (string) ($entry['type'] ?? 'script'),
            'sprite_id' => is_int($entry['sprite_id'] ?? null) ? $entry['sprite_id'] : null,
            'display_sprite_id' => is_int($entry['display_sprite_id'] ?? null) ? $entry['display_sprite_id'] : null,
            'image_available' => (bool) ($entry['image_available'] ?? false),
            'enabled' => (bool) ($entry['enabled'] ?? true),
            'game_visible' => (bool) ($entry['game_visible'] ?? false),
            'catalog_order' => (int) ($entry['catalog_order'] ?? 0),
            'payload' => json_encode(
                Arr::except($entry, [...self::COLUMNS, 'id']),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
            ),
            'sync_token' => $token, 'created_at' => $now, 'updated_at' => $now,
        ];
    }
}
