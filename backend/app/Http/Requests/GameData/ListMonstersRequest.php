<?php

namespace App\Http\Requests\GameData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListMonstersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:100'],
            'race' => ['nullable', 'string', 'max:32'],
            'element' => ['nullable', 'string', 'max:32'],
            'size' => ['nullable', Rule::in(['Small', 'Medium', 'Large'])],
            'kind' => ['nullable', Rule::in(['all', 'normal', 'mini', 'mvp'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
