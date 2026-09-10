<?php

namespace App\Http\Requests\AdventureTools;

use Illuminate\Foundation\Http\FormRequest;

final class ListAdventureItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:32'],
            'subtype' => ['nullable', 'string', 'max:32'],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
