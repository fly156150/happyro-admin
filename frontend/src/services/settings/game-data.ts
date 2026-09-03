import { request } from '@umijs/max';

export type GameDataSettings = {
  clientVersion: string;
  serverVersion: string;
  available: { client: string[]; server: string[] };
};

export type GameDataSettingInput = Pick<
  GameDataSettings,
  'clientVersion' | 'serverVersion'
>;

export async function getGameDataSettings() {
  return request<{ data: GameDataSettings }>('/api/settings/game-data');
}

export async function updateGameDataSettings(data: GameDataSettingInput) {
  return request<{ data: GameDataSettingInput }>('/api/settings/game-data', {
    method: 'PUT',
    data,
  });
}
