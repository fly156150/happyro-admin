<?php

namespace App\Contracts\GameData;

use App\Data\GameData\NpcQuery;

interface NpcRepository
{
    /** @return array{data: list<array<string, mixed>>, total: int} */
    public function search(NpcQuery $query): array;

    /**
     * List every NPC placed on a single map, ordered for display.
     *
     * Map detail views assemble one whole map, so this deliberately returns the
     * complete set instead of a page.
     *
     * @return list<array<string, mixed>>
     */
    public function onMap(string $map, bool $gameVisibleOnly): array;
}
