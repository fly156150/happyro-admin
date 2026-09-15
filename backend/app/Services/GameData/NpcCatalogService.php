<?php

namespace App\Services\GameData;

use App\Contracts\GameData\NpcRepository;
use App\Data\GameData\NpcQuery;

final readonly class NpcCatalogService
{
    public function __construct(private NpcRepository $npcs) {}

    /** @return array{data: list<array<string, mixed>>, total: int} */
    public function search(NpcQuery $query): array
    {
        $result = $this->npcs->search($query);
        $result['data'] = array_map($this->present(...), $result['data']);

        return $result;
    }

    /**
     * Every NPC on one map, for map detail assembly. Intentionally unpaginated.
     *
     * @return list<array<string, mixed>>
     */
    public function onMap(string $map, bool $gameVisibleOnly): array
    {
        return array_map($this->present(...), $this->npcs->onMap($map, $gameVisibleOnly));
    }

    /** @param array<string, mixed> $npc @return array<string, mixed> */
    private function present(array $npc): array
    {
        $spriteId = $npc['display_sprite_id'] ?? null;
        $npc['image'] = $npc['image_available'] && is_int($spriteId)
            ? "/api/game-data/npcs/{$spriteId}/image?v=".config('happyro.game_data.world_asset_version')
            : null;

        return $npc;
    }
}
