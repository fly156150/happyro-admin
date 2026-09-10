import { request } from '@umijs/max';
import type { GameDataNpc } from './npcCatalog';

export async function listNpcs() {
  return request<{
    data: GameDataNpc[];
  }>('/api/game-data/npcs');
}
