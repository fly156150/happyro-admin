import { request } from '@umijs/max';

export async function listMaps() {
  return request<{
    data: {
      id: number | null;
      map: string;
      name_zh_cn?: string;
      image?: string;
    }[];
  }>('/api/game-data/maps');
}
