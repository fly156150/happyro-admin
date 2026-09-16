<?php

namespace App\Infrastructure\Persistence\GameData;

use App\Contracts\GameData\MonsterCatalogRepository;
use App\Data\GameData\MonsterCatalogSnapshot;
use App\Data\GameData\MonsterKind;
use App\Models\GameDataCatalog;
use App\Models\GameMonster;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class DatabaseMonsterCatalogRepository implements MonsterCatalogRepository
{
    public function __construct(private DatabaseManager $database) {}

    public function replace(MonsterCatalogSnapshot $snapshot): array
    {
        return $this->database->transaction(fn (): array => $this->persist($snapshot));
    }

    /** @return array{version: string, imported: int, deleted: int} */
    private function persist(MonsterCatalogSnapshot $snapshot): array
    {
        $catalog = GameDataCatalog::query()->firstOrCreate([
            'resource_type' => 'monsters', 'source' => 'server',
            'ruleset' => 'renewal', 'source_version' => $snapshot->version,
        ], [
            'content_hash' => $snapshot->contentHash, 'record_count' => 0,
            'source_metadata' => $snapshot->metadata, 'imported_at' => now(),
        ]);
        $token = (string) Str::uuid();
        $now = now();
        foreach (array_chunk($snapshot->monsters, 500, true) as $monsters) {
            $rows = [];
            foreach ($monsters as $id => $monster) {
                $rows[] = $this->row($catalog->id, $id, $monster, $token, $now);
            }
            GameMonster::query()->upsert($rows, ['game_data_catalog_id', 'monster_id'], [
                'aegis_name', 'name_zh_cn', 'name_en_us', 'level', 'hp', 'size', 'race',
                'element', 'element_level', 'kind', 'sprite_name', 'payload', 'sync_token', 'updated_at',
            ]);
        }
        $deleted = GameMonster::query()->where('game_data_catalog_id', $catalog->id)
            ->where('sync_token', '!=', $token)->delete();
        $catalog->update([
            'content_hash' => $snapshot->contentHash, 'record_count' => count($snapshot->monsters),
            'source_metadata' => $snapshot->metadata, 'imported_at' => $now,
        ]);

        return ['version' => $snapshot->version, 'imported' => count($snapshot->monsters), 'deleted' => $deleted];
    }

    /** @param array<string, mixed> $monster @return array<string, mixed> */
    private function row(int $catalogId, int|string $id, array $monster, string $token, mixed $now): array
    {
        $names = $monster['names'] ?? null;
        if (! ctype_digit((string) $id) || ! is_array($names)
            || ! is_string($names['zh-CN'] ?? null) || ! is_string($names['en-US'] ?? null)) {
            throw new RuntimeException("Invalid monster snapshot entry: {$id}");
        }

        return [
            'game_data_catalog_id' => $catalogId, 'monster_id' => (int) $id,
            'aegis_name' => (string) ($monster['AegisName'] ?? ''),
            'name_zh_cn' => $names['zh-CN'], 'name_en_us' => $names['en-US'],
            'level' => (int) ($monster['Level'] ?? 1), 'hp' => (int) ($monster['Hp'] ?? 1),
            'size' => (string) ($monster['Size'] ?? 'Small'), 'race' => (string) ($monster['Race'] ?? 'Formless'),
            'element' => (string) ($monster['Element'] ?? 'Neutral'),
            'element_level' => (int) ($monster['ElementLevel'] ?? 1),
            'kind' => MonsterKind::fromSnapshot($monster),
            'sprite_name' => is_string($monster['spriteName'] ?? null) ? $monster['spriteName'] : null,
            'payload' => json_encode(Arr::except($monster, ['names']), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'sync_token' => $token, 'created_at' => $now, 'updated_at' => $now,
        ];
    }
}
