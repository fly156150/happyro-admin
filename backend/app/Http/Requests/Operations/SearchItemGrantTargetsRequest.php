<?php

namespace App\Http\Requests\Operations;

use Illuminate\Foundation\Http\FormRequest;

final class SearchItemGrantTargetsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['target' => trim((string) $this->input('target'))]);
    }

    public function rules(): array
    {
        return ['target' => ['required', 'string', 'max:64']];
    }
}
