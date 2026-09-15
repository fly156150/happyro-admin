<?php

namespace App\Services\GameData;

use App\Contracts\GameData\NpcCatalogRepository;
use App\Contracts\GameData\NpcSnapshotReader;

final readonly class ImportNpcsService
{
    public function __construct(
        private NpcSnapshotReader $snapshots,
        private NpcCatalogRepository $catalogs,
    ) {}

    /** @return array{version: string, imported: int, deleted: int} */
    public function import(string $path): array
    {
        return $this->catalogs->replace($this->snapshots->read($path));
    }
}
