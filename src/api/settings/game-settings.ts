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
  idempotency_key: string;
  changes: Record<string, number>;
}) {
  return request<{ data: unknown }>('/api/settings/game-settings', {
    method: 'PUT',
    data,
  });
}
export type SettingRevision = {
  id: string;
  changes: Record<string, number>;
  status: 'prepared' | 'published' | 'applied' | 'failed' | 'uncertain';
  requested_by: number | null;
  requested_game_account_id: number | null;
  created_at: string;
};
export async function listSettingRevisions(params: { current?: number; pageSize?: number }) {
  return request<{ data: SettingRevision[]; definitions: Record<string, GameSettingDefinition>; meta: { total: number } }>('/api/settings/game-settings/history', {
    params: { page: params.current, per_page: params.pageSize },
  });
}
export async function resumeSettingRevision(id: string) {
  return request<{ data: SettingRevision }>(`/api/settings/game-settings/revisions/${encodeURIComponent(id)}/resume`, { method: 'POST' });
}
