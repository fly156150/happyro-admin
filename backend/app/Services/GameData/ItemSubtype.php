<?php

namespace App\Services\GameData;

final class ItemSubtype
{
    /**
     * @param  array<string, mixed>  $item
     */
    public static function fromSnapshot(array $item): ?string
    {
        $type = is_string($item['Type'] ?? null) ? $item['Type'] : null;
        $declared = is_string($item['SubType'] ?? null) ? $item['SubType'] : null;
        if (in_array($type, ['Armor', 'Card'], true)) {
            return self::fromLocations($item['Locations'] ?? null) ?? $declared;
        }

        return $declared;
    }

    /**
     * @param  mixed  $locations
     */
    public static function fromLocations(mixed $locations): ?string
    {
        if (! is_array($locations)) {
            return null;
        }

        $slots = [];
        foreach ($locations as $name => $enabled) {
            if (is_string($name) && $enabled) {
                $slots[] = $name;
            }
        }
        if ($slots === []) {
            return null;
        }

        sort($slots);
        if (count($slots) === 1) {
            return self::singleSlot($slots[0]);
        }

        $head = self::matching($slots, ['Head_Top', 'Head_Mid', 'Head_Low']);
        if ($head !== [] && count($head) === count($slots)) {
            return count($head) === 1 ? $head[0] : 'Head';
        }

        $costumeHead = self::matching($slots, ['Costume_Head_Top', 'Costume_Head_Mid', 'Costume_Head_Low']);
        if ($costumeHead !== [] && count($costumeHead) === count($slots)) {
            return count($costumeHead) === 1 ? $costumeHead[0] : 'Costume_Head';
        }

        if (count($slots) >= 6) {
            return 'Any';
        }

        foreach ([
            'Head_Top', 'Head_Mid', 'Head_Low', 'Armor', 'Garment', 'Shoes',
            'Left_Hand', 'Both_Accessory', 'Right_Accessory', 'Left_Accessory',
            'Costume_Head_Top', 'Costume_Head_Mid', 'Costume_Head_Low', 'Costume_Garment',
            'Right_Hand', 'Both_Hand',
        ] as $slot) {
            if (in_array($slot, $slots, true)) {
                return self::singleSlot($slot);
            }
        }

        return $slots[0];
    }

    /**
     * @param  list<string>  $slots
     * @param  list<string>  $group
     * @return list<string>
     */
    private static function matching(array $slots, array $group): array
    {
        return array_values(array_intersect($group, $slots));
    }

    private static function singleSlot(string $slot): string
    {
        return match ($slot) {
            'Left_Hand' => 'Shield',
            'Both_Accessory' => 'Accessory',
            'Right_Hand', 'Both_Hand' => 'Weapon',
            default => $slot,
        };
    }
}
