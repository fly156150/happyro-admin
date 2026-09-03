<?php

namespace App\Contracts\GameData;

interface ItemViewBuilder
{
    /** @return array{combinations: int, records: int} */
    public function rebuildAll(): array;
}
