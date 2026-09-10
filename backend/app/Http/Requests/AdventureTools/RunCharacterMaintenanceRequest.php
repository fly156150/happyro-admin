<?php

namespace App\Http\Requests\AdventureTools;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class RunCharacterMaintenanceRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:64'],
            'type' => ['required', 'string', Rule::in([
                'character.progression.update',
                'character.skill_points.update',
                'character.stats.update',
                'character.stats.reset',
                'character.skills.reset',
                'character.vitals.restore',
            ])],
            'payload' => ['present', 'array'],
            'payload.base_level' => ['sometimes', 'integer', 'min:1'],
            'payload.job_level' => ['sometimes', 'integer', 'min:1'],
            'payload.job_id' => ['sometimes', 'integer', 'min:0'],
            'payload.skill_points' => ['sometimes', 'integer', 'between:0,32767'],
            'payload.str' => ['sometimes', 'integer', 'min:1'],
            'payload.agi' => ['sometimes', 'integer', 'min:1'],
            'payload.vit' => ['sometimes', 'integer', 'min:1'],
            'payload.int' => ['sometimes', 'integer', 'min:1'],
            'payload.dex' => ['sometimes', 'integer', 'min:1'],
            'payload.luk' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $allowed = match ($this->string('type')->toString()) {
                'character.progression.update' => ['base_level', 'job_level', 'job_id'],
                'character.skill_points.update' => ['skill_points'],
                'character.stats.update' => ['str', 'agi', 'vit', 'int', 'dex', 'luk'],
                'character.vitals.restore' => ['vitals'],
                default => [],
            };
            if (array_diff(array_keys($this->array('payload')), $allowed) !== []) {
                $validator->errors()->add('payload', '操作参数无效');
            }

            if (in_array($this->string('type')->toString(), ['character.progression.update', 'character.skill_points.update', 'character.stats.update'], true)
                && $this->array('payload') === []) {
                $validator->errors()->add('payload', '至少需要修改一个字段');
            }
        }];
    }
}
