export const actionFields = {
  progression: ['base_level', 'job_level', 'job_id'],
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
  return { ...character, ...result };
}

export function characterFormValues(
  action: MaintenanceAction,
  character: Record<string, unknown>,
) {
  return Object.fromEntries(
    actionFields[action].map((field) => {
      const source = field === 'job_id' ? 'class' : field;
      return [field, Number(character[source])];
    }),
  );
}
