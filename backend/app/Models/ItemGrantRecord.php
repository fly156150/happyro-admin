<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ItemGrantRecord extends Model
{
    protected $fillable = ['idempotency_key', 'request_hash', 'item_id', 'char_id', 'amount', 'title', 'status', 'mail_id', 'requested_by', 'error'];

    protected $hidden = ['idempotency_key', 'request_hash', 'error'];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
