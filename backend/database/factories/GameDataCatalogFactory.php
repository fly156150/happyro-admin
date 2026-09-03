<?php

namespace Database\Factories;

use App\Models\GameDataCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameDataCatalog>
 */
class GameDataCatalogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'resource_type' => 'items',
            'source' => 'server',
            'ruleset' => 'renewal',
            'source_version' => fake()->unique()->regexify('[a-f0-9]{10}'),
            'content_hash' => fake()->sha256(),
            'record_count' => 0,
            'source_metadata' => [],
            'imported_at' => now(),
        ];
    }
}
