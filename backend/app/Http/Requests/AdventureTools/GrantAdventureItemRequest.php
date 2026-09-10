<?php

namespace App\Http\Requests\AdventureTools;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class GrantAdventureItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:64'],
            'target' => ['required', 'array'],
            'target.type' => ['required', 'string', Rule::in(['self'])],
            'item_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'integer', 'min:1', 'max:30000'],
        ];
    }
}
