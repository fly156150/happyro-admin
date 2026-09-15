import { request } from '@umijs/max';
import type { GameDataNpc, NpcVisibility } from './npcCatalog';

export type ListNpcsParams = {
  map?: string;
  name?: string;
  name_zh_cn?: string;
  visibility?: NpcVisibility;
  page?: number;
  perPage?: number;
};

export async function listNpcs(params: ListNpcsParams = {}) {
  return request<{ data: GameDataNpc[]; total: number }>('/api/game-data/npcs', {
    params,
  });
}

/**
 * Every NPC placed on one map. Map detail assembles a whole map, so the server
 * returns the complete set instead of a page.
 */
export async function listMapNpcs(map: string) {
  return request<{ data: GameDataNpc[]; total: number }>(
    `/api/game-data/maps/${map}/npcs`,
  );
}
