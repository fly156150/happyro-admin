<?php

namespace App\Data\GameData;

final class MonsterKind
{
    public const NORMAL = 'normal';

    public const MINI = 'mini';

    public const MVP = 'mvp';

    /** @param array<string, mixed> $monster */
    public static function fromSnapshot(array $monster): string
    {
        if (! empty($monster['MvpDrops'])) {
            return self::MVP;
        }

        return ($monster['Class'] ?? 'Normal') === 'Boss' ? self::MINI : self::NORMAL;
    }
}
