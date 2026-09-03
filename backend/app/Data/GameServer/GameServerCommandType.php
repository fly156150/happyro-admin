<?php

namespace App\Data\GameServer;

enum GameServerCommandType: string
{
    case CharacterProgressionUpdate = 'character.progression.update';
    case CharacterStatsUpdate = 'character.stats.update';
    case CharacterStatsReset = 'character.stats.reset';
    case CharacterSkillsReset = 'character.skills.reset';
    case CharacterVitalsRestore = 'character.vitals.restore';
    case MonsterSpawn = 'monster.spawn';
    case BattleConfigApply = 'battle_config.apply';
}
