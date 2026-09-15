<?php

namespace App\Http\Requests\GameData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListMapsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<mixed>|string> */
    public function rules(): array
    {
        return [
            'scope' => ['nullable', Rule::in(['game', 'all'])],
            'map' => ['nullable', 'string', 'max:64'],
            'name_zh_cn' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
