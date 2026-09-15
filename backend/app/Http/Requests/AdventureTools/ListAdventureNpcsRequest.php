<?php

namespace App\Http\Requests\AdventureTools;

use Illuminate\Foundation\Http\FormRequest;

final class ListAdventureNpcsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<mixed>|string> */
    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:100'],
            'onMap' => ['nullable', 'string', 'max:64'],
            'currentMap' => ['nullable', 'string', 'max:64'],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
