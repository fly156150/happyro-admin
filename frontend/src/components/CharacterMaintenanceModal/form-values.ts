import type { CharacterSnapshot } from '@/services/operations/game-control';

export const actionFields = {
  job: ['job_id'],
  progression: ['base_level', 'job_level'],
  skillPoints: ['skill_points'],
  stats: ['str', 'agi', 'vit', 'int', 'dex', 'luk'],
  statsReset: [],
  traits: ['pow', 'sta', 'wis', 'spl', 'con', 'crt'],
  traitsReset: [],
  skillsLearnAll: [],
  skills: [],
  vitals: [],
} as const;

export type MaintenanceAction = keyof typeof actionFields;

export function jobChangePayload(jobId: number, character: CharacterSnapshot) {
  const job = character.jobs?.find((entry) => entry.id === jobId);
  if (!job) throw new Error('请选择当前可用的职业');
  return {
    job_id: jobId,
    base_level: Math.min(Number(character.base_level), job.max_base_level),
    job_level: Math.min(Number(character.job_level), job.max_job_level),
  };
}

export function mergeCharacterResult(
  character: Record<string, unknown> | undefined,
  result: Record<string, unknown> | undefined,
) {
  return {
    ...character,
    ...result,
  };
}

export function characterFormValues(
  action: MaintenanceAction,
  character: CharacterSnapshot,
) {
  return Object.fromEntries(
    actionFields[action].map((field) => {
      return [
        field,
        Number(
          action === 'traits'
            ? character.traits?.values[field]
            : character[field],
        ),
      ];
    }),
  );
}

export function fieldMaximum(
  action: MaintenanceAction,
  field: string,
  character?: CharacterSnapshot,
) {
  if (action === 'traits') return character?.traits?.maximums[field];
  if (action === 'stats') return character?.max_stats?.[field];
  if (field === 'base_level') return character?.max_base_level;
  if (field === 'job_level') return character?.max_job_level;
  if (field === 'skill_points') return character?.max_skill_points;
  return undefined;
}
