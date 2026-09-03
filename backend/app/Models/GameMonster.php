<?php

namespace App\Models;

use Database\Factories\GameMonsterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameMonster extends Model
{
    /** @use HasFactory<GameMonsterFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_boss' => 'boolean', 'payload' => 'array'];
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(GameDataCatalog::class, 'game_data_catalog_id');
    }
}
