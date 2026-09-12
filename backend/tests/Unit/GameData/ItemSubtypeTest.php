<?php

namespace Tests\Unit\GameData;

use App\Services\GameData\ItemSubtype;
use PHPUnit\Framework\TestCase;

final class ItemSubtypeTest extends TestCase
{
    public function test_weapons_keep_declared_weapon_type(): void
    {
        $this->assertSame('1hSword', ItemSubtype::fromSnapshot([
            'Type' => 'Weapon',
            'SubType' => '1hSword',
            'Locations' => ['Right_Hand' => true],
        ]));
    }

    public function test_armor_uses_wear_location(): void
    {
        $this->assertSame('Head_Top', ItemSubtype::fromSnapshot([
            'Type' => 'Armor',
            'Locations' => ['Head_Top' => true],
        ]));
        $this->assertSame('Shield', ItemSubtype::fromSnapshot([
            'Type' => 'Armor',
            'Locations' => ['Left_Hand' => true],
        ]));
        $this->assertSame('Head', ItemSubtype::fromSnapshot([
            'Type' => 'Armor',
            'Locations' => ['Head_Top' => true, 'Head_Mid' => true],
        ]));
    }

    public function test_cards_use_wear_location(): void
    {
        $this->assertSame('Weapon', ItemSubtype::fromSnapshot([
            'Type' => 'Card',
            'SubType' => 'Enchant',
            'Locations' => ['Right_Hand' => true],
        ]));
        $this->assertSame('Enchant', ItemSubtype::fromSnapshot([
            'Type' => 'Card',
            'SubType' => 'Enchant',
        ]));
        $this->assertSame('Any', ItemSubtype::fromSnapshot([
            'Type' => 'Card',
            'Locations' => [
                'Armor' => true,
                'Both_Accessory' => true,
                'Both_Hand' => true,
                'Garment' => true,
                'Head_Low' => true,
                'Head_Mid' => true,
                'Head_Top' => true,
                'Shoes' => true,
            ],
        ]));
    }

    public function test_ammo_keeps_declared_subtype(): void
    {
        $this->assertSame('Arrow', ItemSubtype::fromSnapshot([
            'Type' => 'Ammo',
            'SubType' => 'Arrow',
        ]));
    }
}
