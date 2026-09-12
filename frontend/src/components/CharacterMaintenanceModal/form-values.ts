export const actionFields = {
  job: ['job_id'],
  progression: ['base_level', 'job_level'],
  skillPoints: ['skill_points'],
  stats: ['str', 'agi', 'vit', 'int', 'dex', 'luk'],
  statsReset: [],
  skills: [],
  vitals: [],
} as const;

export type MaintenanceAction = keyof typeof actionFields;

export const maxMaintainedStat = 32767;

export function mergeCharacterResult(
  character: Record<string, unknown> | undefined,
  result: Record<string, number> | undefined,
) {
  return {
    ...character,
    ...result,
    ...(result?.job_id !== undefined ? { class: result.job_id } : {}),
    ...(result?.skill_points !== undefined
      ? { skill_point: result.skill_points }
      : {}),
  };
}

export function characterFormValues(
  action: MaintenanceAction,
  character: Record<string, unknown>,
) {
  return Object.fromEntries(
    actionFields[action].map((field) => {
      const source =
        field === 'job_id'
          ? 'class'
          : field === 'skill_points'
            ? 'skill_point'
            : field;
      return [field, Number(character[source])];
    }),
  );
}
