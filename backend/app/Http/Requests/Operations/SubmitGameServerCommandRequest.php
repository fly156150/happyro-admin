<?php

namespace App\Http\Requests\Operations;

use App\Data\GameServer\GameServerCommandType;
use App\Http\Requests\CharacterMaintenancePayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'type' => ['required', 'string', Rule::in([...GameServerCommandType::characterMaintenanceValues(), 'monster.spawn', GameServerCommandType::CharacterNavigationTeleport->value, GameServerCommandType::CharacterNavigationRoute->value])],
            'target' => ['required', 'array'],
            'target.type' => ['required', 'string', 'in:character'],
            'target.id' => ['required', 'string', 'max:64'],
            'payload' => ['present', 'array'],
            ...match ($this->string('type')->toString()) {
                'monster.spawn' => [],
                'character.navigation.route' => $this->input('payload.action') === 'stop' ? [
                    'payload' => ['required', 'array:action'],
                    'payload.action' => ['required', 'in:stop'],
                ] : [
                    'payload' => ['required', 'array:map,x,y,npc_class,action'],
                    'payload.action' => ['required', 'string', 'in:walk,preview'],
                    'payload.map' => ['required', 'string', 'max:15', 'regex:/^[a-zA-Z0-9_@-]+$/D'],
                    'payload.x' => ['required', 'integer', 'between:0,32767'],
                    'payload.y' => ['required', 'integer', 'between:0,32767'],
                    'payload.npc_class' => ['sometimes', 'integer', 'between:1,32767'],
                ],
                'character.navigation.teleport' => [
                    'payload' => ['required', 'array:map,x,y,npc_class'],
                    'payload.map' => ['required', 'string', 'max:15', 'regex:/^[a-zA-Z0-9_@-]+$/D'],
                    'payload.x' => ['required', 'integer', 'between:0,32767'],
                    'payload.y' => ['required', 'integer', 'between:0,32767'],
                    'payload.npc_class' => ['sometimes', 'integer', 'between:1,32767'],
                ],
                default => CharacterMaintenancePayload::rules($this->string('type')->toString()),
            },
        ];
    }
}
