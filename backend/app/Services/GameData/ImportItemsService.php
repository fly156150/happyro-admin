<?php

namespace App\Services\GameData;

use App\Contracts\GameData\ItemCatalogRepository;
use App\Contracts\GameData\ItemSnapshotReader;
use App\Contracts\GameData\ItemViewBuilder;

final class ImportItemsService
{
    public function __construct(
        private readonly ItemSnapshotReader $snapshots,
        private readonly ItemViewBuilder $views,
        private readonly ItemCatalogRepository $catalogs,
    ) {}

    /** @return array{source: string, ruleset: string, version: string, imported: int, deleted: int, views: int} */
    public function import(string $path): array
    {
        return $this->importMany([$path])[0];
    }

    /**
     * @param  list<string>  $paths
     * @return list<array{source: string, ruleset: string, version: string, imported: int, deleted: int, views: int}>
     */
    public function importMany(array $paths): array
    {
        $snapshots = array_map($this->snapshots->read(...), $paths);
        $results = $this->catalogs->replaceMany($snapshots);
        $viewCount = $this->views->rebuildAll()['records'];

        return array_map(fn (array $result): array => [...$result, 'views' => $viewCount], $results);
    }
}
