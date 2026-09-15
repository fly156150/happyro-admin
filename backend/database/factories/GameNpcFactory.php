<?php

namespace Database\Factories;

use App\Models\GameDataCatalog;
use App\Models\GameNpc;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameNpc>
 */
class GameNpcFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'game_data_catalog_id' => GameDataCatalog::factory()->state(['resource_type' => 'npcs']),
            'npc_key' => 'prontera:100:100:'.$name,
            'map' => 'prontera',
            'map_name_zh_cn' => '普隆德拉',
            'x' => 100,
            'y' => 100,
            'name' => $name,
            'source_name' => $name,
            'display_name' => $name,
            'type' => 'script',
            'sprite_id' => 419,
            'display_sprite_id' => 419,
            'image_available' => true,
            'enabled' => true,
            'game_visible' => true,
            'catalog_order' => fake()->unique()->numberBetween(1, 9999),
            'payload' => ['direction' => 0, 'dynamic' => false, 'navigation' => null],
            'sync_token' => fake()->uuid(),
        ];
    }
}
