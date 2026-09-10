<?php

namespace App\Http\Requests\Operations;

use Illuminate\Foundation\Http\FormRequest;

final class GrantCharacterZenyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:64'],
            'char_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'integer', 'min:1', 'max:2147483647'],
        ];
    }
}
