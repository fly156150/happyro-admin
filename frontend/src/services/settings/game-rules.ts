import { request } from '@umijs/max';

export type GameRuleDefinition = {
  key: string;
  minimum: number;
  maximum: number;
  source: string;
  unit: string;
};

export type GameRuleSettings = {
  values: Record<string, number>;
  definitions: Record<string, GameRuleDefinition>;
};

export async function getGameRuleSettings() {
  return request<{ data: GameRuleSettings }>('/api/settings/game-rules');
}

export async function updateGameRuleSettings(data: {
  changes: Record<string, number>;
  reason: string;
}) {
  return request<{ data: unknown }>('/api/settings/game-rules', {
    method: 'PUT',
    data,
  });
}
