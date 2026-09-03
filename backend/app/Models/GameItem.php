<?php

namespace App\Models;

use Database\Factories\GameItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameItem extends Model
{
    /** @use HasFactory<GameItemFactory> */
    use HasFactory;

    protected $fillable = ['item_id'];

    public function sources(): HasMany
    {
        return $this->hasMany(GameItemSource::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(GameItemView::class);
    }

    protected function casts(): array
    {
        return ['item_id' => 'integer'];
    }
}
