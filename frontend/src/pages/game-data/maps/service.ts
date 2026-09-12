import { request } from '@umijs/max';

export async function listMaps(scope: 'game' | 'all' = 'game') {
  return request<{
    data: {
      id: number | null;
      map: string;
      name_zh_cn?: string;
      image?: string;
      supported: boolean;
      image_kind?: 'image' | 'terrain' | null;
    }[];
  }>('/api/game-data/maps', { params: { scope } });
}
