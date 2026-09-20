<?php

namespace App\Http\Requests\AdventureTools;

use App\Data\GameServer\GameServerCommandType;
use App\Http\Requests\CharacterMaintenancePayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RunCharacterMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:64'],
            'type' => ['required', 'string', Rule::in(GameServerCommandType::characterMaintenanceValues())],
            ...CharacterMaintenancePayload::rules($this->string('type')->toString()),
        ];
    }

    public function messages(): array
    {
        return ['payload.array' => '操作参数无效', 'payload.required' => '至少需要修改一个字段'];
    }
}
