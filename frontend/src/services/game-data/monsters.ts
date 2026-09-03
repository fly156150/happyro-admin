import { request } from '@umijs/max';

export type GameDataMonster = {
  Id: number;
  image?: string;
  AegisName: string;
  names: Record<string, string>;
  Level: number;
  Hp: number;
  Size: string;
  Race: string;
  Element: string;
  ElementLevel: number;
  BaseExp?: number;
  JobExp?: number;
  Attack?: number;
  Attack2?: number;
  Defense?: number;
  MagicDefense?: number;
  Str?: number;
  Agi?: number;
  Vit?: number;
  Int?: number;
  Dex?: number;
  Luk?: number;
  isBoss: boolean;
  serverVersion: string;
  Drops?: { Item: string; Rate: number }[];
  MvpDrops?: { Item: string; Rate: number }[];
};

export function monsterName(monster: GameDataMonster, locale: string): string {
  return monster.names[locale] || monster.names['en-US'] || String(monster.Id);
}

export async function listMonsters(params: Record<string, unknown>) {
  const { current, pageSize, ...filters } = params;
  return request<{ data: GameDataMonster[]; total: number }>(
    '/api/game-data/monsters',
    { params: { ...filters, page: current, perPage: pageSize } },
  );
}

export async function getMonster(id: number) {
  return request<{ data: GameDataMonster }>(`/api/game-data/monsters/${id}`);
}
