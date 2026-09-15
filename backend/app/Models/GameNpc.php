<?php

namespace App\Models;

use Database\Factories\GameNpcFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameNpc extends Model
{
    /** @use HasFactory<GameNpcFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'image_available' => 'boolean',
            'enabled' => 'boolean',
            'game_visible' => 'boolean',
            'payload' => 'array',
        ];
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(GameDataCatalog::class, 'game_data_catalog_id');
    }
}
