<?php

namespace App\Http\Requests\AdventureTools;

use Illuminate\Foundation\Http\FormRequest;

final class GrantAdventureZenyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:64'],
            'amount' => ['required', 'integer', 'min:1', 'max:2147483647'],
        ];
    }
}
