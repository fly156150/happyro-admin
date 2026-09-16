<?php

namespace App\Http\Requests\Settings;

use App\Data\GameServer\GameServerSettingDefinition;
use App\Services\GameServer\GameServerSettingRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class ApplyGameServerSettingsRequest extends FormRequest
{
    /** @var array<string, GameServerSettingDefinition> */
    private array $definitions = [];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(GameServerSettingRegistry $registry): array
    {
        $this->definitions = $registry->definitions();

        return [
            'changes' => ['required', 'array', 'min:1'],
            'changes.*' => ['required', 'integer', 'min:0'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach (array_keys($this->input('changes', [])) as $key) {
                if (! isset($this->definitions[$key])) {
                    $validator->errors()->add("changes.$key", 'Unknown game server setting.');

                    continue;
                }

                $value = $this->input("changes.$key");
                if (is_numeric($value) && (int) $value < $this->definitions[$key]->minimum) {
                    $validator->errors()->add("changes.$key", __('messages.game_server_setting_out_of_range'));
                }
                if (is_numeric($value) && (int) $value > $this->definitions[$key]->maximum) {
                    $validator->errors()->add("changes.$key", __('messages.game_server_setting_out_of_range'));
                }
            }
        });
    }
}
