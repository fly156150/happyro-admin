<?php

namespace App\Models;

use Database\Factories\GameItemSourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameItemSource extends Model
{
    /** @use HasFactory<GameItemSourceFactory> */
    use HasFactory;

    protected $fillable = [
        'game_item_id',
        'game_data_catalog_id',
        'name_zh_cn',
        'name_en_us',
        'aegis_name',
        'item_type',
        'item_subtype',
        'resource_name',
        'description',
        'payload',
        'sync_token',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(GameItem::class, 'game_item_id');
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(GameDataCatalog::class, 'game_data_catalog_id');
    }

    protected function casts(): array
    {
        return [
            'description' => 'array',
            'payload' => 'array',
        ];
    }
}
