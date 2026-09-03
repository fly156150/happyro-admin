<?php

namespace Database\Factories;

use App\Models\GameDataSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameDataSetting>
 */
class GameDataSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_version' => 'kro-20211105',
            'server_version' => '2fe6ab3dc4d8',
        ];
    }
}
