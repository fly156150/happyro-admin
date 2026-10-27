import { request } from '@umijs/max';

export type MapRow = {
  id: number | null;
  map: string;
  name_zh_cn?: string;
  image?: string;
  supported: boolean;
  player_hidden: boolean;
  channel?: number | null;
  image_kind?: 'image' | 'terrain' | null;
};

export type ListMapsParams = {
  scope?: 'game' | 'all';
  query?: string;
  page?: number;
  perPage?: number;
};

export async function listMaps(params: ListMapsParams = {}) {
  return request<{ data: MapRow[]; total: number }>('/api/game-data/maps', {
    params,
  });
}
