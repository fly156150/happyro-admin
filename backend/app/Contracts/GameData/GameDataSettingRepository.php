<?php

namespace App\Contracts\GameData;

use App\Data\GameData\GameDataVersions;

interface GameDataSettingRepository
{
    public function current(): GameDataVersions;

    /** @return array{client: list<string>, server: list<string>} */
    public function available(): array;
}
