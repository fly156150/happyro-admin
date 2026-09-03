<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class GameServerSetting extends Model
{
    protected $fillable = [
        'server_key',
        'setting_key',
        'desired_value',
        'actual_value',
        'status',
        'revision_id',
    ];

    protected function casts(): array
    {
        return [
            'desired_value' => 'integer',
            'actual_value' => 'integer',
        ];
    }
}
