<?php

namespace Tests\Unit\GameData;

use App\Data\GameData\MonsterKind;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MonsterKindTest extends TestCase
{
    #[DataProvider('snapshotKinds')]
    public function test_classifies_snapshot_monsters(array $monster, string $kind): void
    {
        $this->assertSame($kind, MonsterKind::fromSnapshot($monster));
    }

    /** @return array<string, array{0: array<string, mixed>, 1: string}> */
    public static function snapshotKinds(): array
    {
        return [
            'normal' => [['Class' => 'Normal'], MonsterKind::NORMAL],
            'mini' => [['Class' => 'Boss'], MonsterKind::MINI],
            'mvp' => [['Class' => 'Boss', 'MvpDrops' => [['Item' => 'Baphomet_Card', 'Rate' => 1]]], MonsterKind::MVP],
            'mvp without class' => [['MvpDrops' => [['Item' => 'Osiris_Doll', 'Rate' => 500]]], MonsterKind::MVP],
        ];
    }
}
