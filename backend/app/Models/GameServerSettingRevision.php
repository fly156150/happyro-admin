<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class GameServerSettingRevision extends Model
{
    protected $fillable = [
        'server_key',
        'revision',
        'changes',
        'status',
        'requested_by',
        'requested_game_account_id',
        'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'applied_at' => 'datetime',
            'requested_game_account_id' => 'integer',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
