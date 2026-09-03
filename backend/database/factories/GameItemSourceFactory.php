<?php

namespace Database\Factories;

use App\Models\GameDataCatalog;
use App\Models\GameItem;
use App\Models\GameItemSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GameItemSource> */
class GameItemSourceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'game_item_id' => GameItem::factory(),
            'game_data_catalog_id' => GameDataCatalog::factory(),
            'name_zh_cn' => '测试物品',
            'name_en_us' => fake()->words(2, true),
            'aegis_name' => fake()->unique()->regexify('[A-Z][A-Za-z_]{12}'),
            'item_type' => 'Etc',
            'item_subtype' => null,
            'resource_name' => null,
            'description' => null,
            'payload' => [],
            'sync_token' => fake()->uuid(),
        ];
    }
}
