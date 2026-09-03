import { request } from '@umijs/max';

export async function listNpcs() {
  return request<{
    data: {
      map: string;
      map_name_zh_cn?: string;
      x: number;
      y: number;
      name: string;
      name_zh_cn?: string;
      image?: string;
    }[];
  }>('/api/game-data/npcs');
}
