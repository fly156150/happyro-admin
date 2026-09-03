<?php

namespace App\Models;

use App\Data\GameServer\GameServerCommandStatus;
use App\Data\GameServer\GameServerCommandType;
use Illuminate\Database\Eloquent\Model;

final class GameServerCommandRecord extends Model
{
    protected $table = 'game_server_commands';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'idempotency_key',
        'request_hash',
        'type',
        'status',
        'target_type',
        'target_id',
        'payload',
        'result',
        'error_code',
        'error_message',
        'requested_by',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => GameServerCommandType::class,
            'status' => GameServerCommandStatus::class,
            'payload' => 'array',
            'result' => 'array',
            'requested_by' => 'integer',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
