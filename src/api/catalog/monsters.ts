import { request } from '@umijs/max';

export type GameDataMonsterDrop = {
  Item: string;
  Rate: number;
  itemId?: number;
  names?: Record<string, string>;
};

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
  kind: 'normal' | 'mini' | 'mvp';
  serverVersion: string;
  Drops?: GameDataMonsterDrop[];
  MvpDrops?: GameDataMonsterDrop[];
};

export function monsterName(monster: GameDataMonster, locale: string): string {
  return monster.names[locale] || monster.names['en-US'] || String(monster.Id);
}

export function monsterDropName(drop: GameDataMonsterDrop, locale: string): string {
  return drop.names?.[locale] || drop.names?.['zh-CN'] || drop.names?.['en-US'] || drop.Item;
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
