import { request } from '@umijs/max';

export type CharacterSnapshot = Record<string, unknown> & {
  max_base_level?: number;
  max_job_level?: number;
  max_skill_points?: number;
  max_stats?: Record<string, number>;
  jobs?: { id: number; max_base_level: number; max_job_level: number }[];
  traits?: {
    enabled: boolean;
    values: Record<string, number>;
    maximums: Record<string, number>;
    points: number;
    budget: number;
  };
};

export async function getCharacterSnapshot(characterId: number) {
  return request<{ data: CharacterSnapshot }>(
    `/api/operations/game-control/characters/${characterId}`,
  );
}

export type GameControlCommand = {
  idempotency_key: string;
  type: string;
  target: { type: 'character'; id: string };
  payload: Record<string, number | string>;
};

export type GameControlCommandResponse = {
  data: { result?: Record<string, unknown> };
};

export async function executeGameControlCommand(data: GameControlCommand) {
  return request<GameControlCommandResponse>(
    '/api/operations/game-control/commands',
    {
      method: 'POST',
      data,
    },
  );
}
