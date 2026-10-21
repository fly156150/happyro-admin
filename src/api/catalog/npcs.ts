import { request } from '@umijs/max';

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

export type NpcVisibility = 'game' | 'all';

export type GameDataNpc = {
  id: string;
  map: string;
  map_name_zh_cn?: string | null;
  x: number;
  y: number;
  name: string;
  source_name: string;
  display_name: string;
  name_zh_cn?: string | null;
  type: string;
  sprite_id?: number | null;
  display_sprite_id?: number | null;
  image_available: boolean;
  enabled: boolean;
  dynamic: boolean;
  game_visible: boolean;
  catalog_order: number;
  navigation?: { id: number; class: number } | null;
  source: { path: string; line: number };
  image?: string | null;
};
