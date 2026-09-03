<?php

namespace App\Http\Requests\Settings;

use App\Services\GameServer\GameServerSettingRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class ApplyGameServerSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'changes' => ['required', 'array', 'min:1'],
            'changes.*' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $definitions = app(GameServerSettingRegistry::class)->definitions();
            foreach (array_keys($this->input('changes', [])) as $key) {
                if (! isset($definitions[$key])) {
                    $validator->errors()->add("changes.$key", 'Unknown game server setting.');

                    continue;
                }

                $value = $this->input("changes.$key");
                if (is_numeric($value) && (int) $value < $definitions[$key]->minimum) {
                    $validator->errors()->add("changes.$key", 'Game server setting value is outside the allowed range.');
                }
                if (is_numeric($value) && (int) $value > $definitions[$key]->maximum) {
                    $validator->errors()->add("changes.$key", 'Game server setting value is outside the allowed range.');
                }
            }
        });
    }
}
