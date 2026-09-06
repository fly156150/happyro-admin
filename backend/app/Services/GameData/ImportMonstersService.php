<?php

namespace App\Services\GameData;

use App\Contracts\GameData\MonsterCatalogRepository;
use App\Contracts\GameData\MonsterSnapshotReader;

final readonly class ImportMonstersService
{
    public function __construct(
        private MonsterSnapshotReader $snapshots,
        private MonsterCatalogRepository $catalogs,
    ) {}

    /** @return array{version: string, imported: int, deleted: int} */
    public function import(string $path): array
    {
        $snapshot = $this->snapshots->read($path);

        return $this->catalogs->replace($snapshot);
    }
}
