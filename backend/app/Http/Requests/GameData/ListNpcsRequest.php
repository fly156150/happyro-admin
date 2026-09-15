<?php

namespace App\Http\Requests\GameData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListNpcsRequest extends FormRequest
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
            'map' => ['nullable', 'string', 'max:64'],
            'name' => ['nullable', 'string', 'max:100'],
            'name_zh_cn' => ['nullable', 'string', 'max:100'],
            'visibility' => ['nullable', Rule::in(['game', 'all'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
