<?php

namespace App\Models;

use Database\Factories\GameDataCatalogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameDataCatalog extends Model
{
    /** @use HasFactory<GameDataCatalogFactory> */
    use HasFactory;

    protected $fillable = [
        'resource_type',
        'source',
        'ruleset',
        'source_version',
        'content_hash',
        'record_count',
        'source_metadata',
        'imported_at',
    ];

    public function itemSources(): HasMany
    {
        return $this->hasMany(GameItemSource::class);
    }

    protected function casts(): array
    {
        return [
            'record_count' => 'integer',
            'source_metadata' => 'array',
            'imported_at' => 'datetime',
        ];
    }
}
