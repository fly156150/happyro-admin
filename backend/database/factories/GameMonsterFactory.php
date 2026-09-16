<?php

namespace Database\Factories;

use App\Models\GameDataCatalog;
use App\Models\GameMonster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameMonster>
 */
class GameMonsterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_data_catalog_id' => GameDataCatalog::factory()->state(['resource_type' => 'monsters']),
            'monster_id' => fake()->unique()->numberBetween(1000, 9999),
            'aegis_name' => strtoupper(fake()->word()),
            'name_zh_cn' => '测试魔物',
            'name_en_us' => fake()->words(2, true),
            'level' => 1,
            'hp' => 55,
            'size' => 'Medium',
            'race' => 'Plant',
            'element' => 'Water',
            'element_level' => 1,
            'kind' => 'normal',
            'payload' => [],
            'sync_token' => fake()->uuid(),
        ];
    }
}
