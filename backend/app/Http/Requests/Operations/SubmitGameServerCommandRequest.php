<?php

namespace App\Http\Requests\Operations;

use Illuminate\Foundation\Http\FormRequest;

final class SubmitGameServerCommandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:64'],
            'type' => ['required', 'string', 'in:character.progression.update,character.stats.update,character.stats.reset,character.skills.reset,character.vitals.restore,monster.spawn'],
            'target' => ['required', 'array'],
            'target.type' => ['required', 'string', 'in:character'],
            'target.id' => ['required', 'string', 'max:64'],
            'payload' => ['present', 'array'],
        ];
    }
}
