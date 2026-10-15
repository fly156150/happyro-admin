<?php

namespace App\Http\Requests\Operations;

use App\Rules\GameMailText;
use Illuminate\Foundation\Http\FormRequest;

final class GrantItemRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['delivery' => $this->input('delivery', 'mail')]);
    }

    public function rules(): array
    {
        return [
            'delivery' => ['required', 'string', 'in:mail,inventory'],
            'item_id' => ['required', 'integer', 'min:1'],
            'char_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'integer', 'min:1', 'max:30000'],
            'title' => ['exclude_unless:delivery,mail', 'required', 'string', new GameMailText(39, '邮件标题')],
            'message' => ['exclude_unless:delivery,mail', 'required', 'string', new GameMailText(499, '邮件内容')],
            'bound' => ['nullable', 'boolean'],
            'idempotency_key' => ['required', 'string', 'max:64'],
        ];
    }
}
