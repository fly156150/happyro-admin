<?php

namespace App\Data\GameData;

final readonly class NpcQuery
{
    /**
     * @param  string|null  $query  Matches any searchable column, for a single search box.
     * @param  string|null  $map  Map code or localized map name substring.
     * @param  string|null  $name  Raw NPC name substring.
     * @param  string|null  $displayName  Localized NPC name substring.
     * @param  string|null  $onMap  Exact map code, for a "current map only" scope.
     */
    public function __construct(
        public ?string $query = null,
        public ?string $map = null,
        public ?string $name = null,
        public ?string $displayName = null,
        public ?string $onMap = null,
        public ?string $currentMap = null,
        public bool $gameVisibleOnly = true,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
