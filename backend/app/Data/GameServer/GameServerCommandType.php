<?php

namespace App\Data\GameServer;

enum GameServerCommandType: string
{
    case CharacterSnapshot = 'character.snapshot';
    case CharacterNavigationTeleport = 'character.navigation.teleport';
    case CharacterNavigationRoute = 'character.navigation.route';
    case CharacterProgressionUpdate = 'character.progression.update';
    case CharacterSkillPointsUpdate = 'character.skill_points.update';
    case CharacterStatsUpdate = 'character.stats.update';
    case CharacterStatsReset = 'character.stats.reset';
    case CharacterTraitsUpdate = 'character.traits.update';
    case CharacterTraitsReset = 'character.traits.reset';
    case CharacterSkillsReset = 'character.skills.reset';
    case CharacterSkillsLearnAll = 'character.skills.learn_all';
    case CharacterVitalsRestore = 'character.vitals.restore';
    case CharacterInventoryItemGrant = 'character.inventory.item_grant';
    case CharacterCurrencyZenyGrant = 'character.currency.zeny_grant';
    case MonsterSpawn = 'monster.spawn';
    case BattleConfigApply = 'battle_config.apply';

    /** @return list<string> */
    public static function characterMaintenanceValues(): array
    {
        return array_map(fn (self $type): string => $type->value, [
            self::CharacterProgressionUpdate, self::CharacterSkillPointsUpdate,
            self::CharacterStatsUpdate, self::CharacterStatsReset,
            self::CharacterTraitsUpdate, self::CharacterTraitsReset,
            self::CharacterSkillsReset, self::CharacterSkillsLearnAll, self::CharacterVitalsRestore,
        ]);
    }
}
