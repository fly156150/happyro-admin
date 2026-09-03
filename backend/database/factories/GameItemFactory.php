<?php

namespace Database\Factories;

use App\Models\GameItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GameItem> */
class GameItemFactory extends Factory
{
    public function definition(): array
    {
        return ['item_id' => fake()->unique()->numberBetween(1, 1_000_000)];
    }
}
