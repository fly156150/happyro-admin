import { request } from '@umijs/max';

export type GameSettingDefinition = {
  key: string;
  minimum: number;
  maximum: number;
  source: string;
  unit: string;
};

export type GameSettings = {
  values: Record<string, number>;
  definitions: Record<string, GameSettingDefinition>;
};

export async function getGameSettings() {
  return request<{ data: GameSettings }>('/api/settings/game-settings');
}

export async function updateGameSettings(data: {
  changes: Record<string, number>;
  remark?: string;
}) {
  return request<{ data: unknown }>('/api/settings/game-settings', {
    method: 'PUT',
    data,
  });
}
