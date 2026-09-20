<?php

namespace App\Http\Requests;

final class CharacterMaintenancePayload
{
    /** @return array<string, array<int, string>> */
    public static function rules(string $type): array
    {
        $fields = match ($type) {
            'character.progression.update' => ['base_level' => 'min:1', 'job_level' => 'min:1', 'job_id' => 'min:0'],
            'character.skill_points.update' => ['skill_points' => 'between:0,32767'],
            'character.stats.update' => array_fill_keys(['str', 'agi', 'vit', 'int', 'dex', 'luk'], 'min:1'),
            'character.traits.update' => array_fill_keys(['pow', 'sta', 'wis', 'spl', 'con', 'crt'], 'min:0'),
            default => [],
        };

        if ($type === 'character.vitals.restore') {
            return [
                'payload' => ['present', 'array:vitals'],
                'payload.vitals' => ['sometimes', 'array'],
                'payload.vitals.*' => ['string', 'in:hp,sp,ap', 'distinct'],
            ];
        }

        if ($fields === []) {
            return ['payload' => ['present', 'array', 'size:0']];
        }

        $rules = ['payload' => ['required', 'array:'.implode(',', array_keys($fields)), 'min:1']];
        foreach ($fields as $field => $limit) {
            $rules['payload.'.$field] = ['sometimes', 'integer', $limit];
        }

        return $rules;
    }
}
