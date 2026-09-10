<?php

namespace App\Http\Requests\AdventureTools;

use App\Services\GameServer\GameServerSettingRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class ApplyGameRulesRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(GameServerSettingRegistry $registry): array
    {
        $definitions = $registry->adventureToolDefinitions();
        $rules = [
            'changes' => ['required', 'array', 'min:1'],
            'changes.*' => ['required', 'integer'],
        ];

        $rules['changes'][] = 'array:'.implode(',', array_keys($definitions));
        foreach ($definitions as $key => $definition) {
            $rules["changes.$key"] = ['sometimes', 'integer', "between:$definition->minimum,$definition->maximum"];
        }

        return $rules;
    }
}
