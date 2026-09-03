<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameItemView extends Model
{
    protected $guarded = [];

    public function item(): BelongsTo
    {
        return $this->belongsTo(GameItem::class, 'game_item_id');
    }

    public function clientCatalog(): BelongsTo
    {
        return $this->belongsTo(GameDataCatalog::class, 'client_catalog_id');
    }

    public function serverCatalog(): BelongsTo
    {
        return $this->belongsTo(GameDataCatalog::class, 'server_catalog_id');
    }

    protected function casts(): array
    {
        return [
            'client_exists' => 'boolean',
            'server_exists' => 'boolean',
            'item_id' => 'integer',
            'description' => 'array',
            'field_sources' => 'array',
            'payload' => 'array',
        ];
    }
}
