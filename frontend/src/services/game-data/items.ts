import { request } from '@umijs/max';

export type GameDataItem = {
  Id: number;
  icon?: string;
  illustration?: string;
  AegisName?: string;
  names: Record<string, string>;
  Type?: string;
  SubType?: string | number;
  Buy?: number;
  Sell?: number;
  Weight?: number;
  Attack?: number;
  Defense?: number;
  Slots?: number;
  EquipLevelMin?: number;
  Script?: string;
  description?: string[];
  source: 'client' | 'server' | 'both';
  clientResourceVersion?: string;
  serverVersion?: string;
};

export type ItemDataRange = 'client' | 'server' | 'all';

export type ItemQuery = {
  query?: string;
  type?: string;
  subtype?: string;
  range?: ItemDataRange;
};

export function itemName(item: GameDataItem, locale: string): string {
  return item.names[locale] || item.names['en-US'] || Object.values(item.names)[0] || String(item.Id);
}

export async function listItems(params: Record<string, unknown>) {
  const { current, pageSize, ...filters } = params;
  return request<{ data: GameDataItem[]; total: number }>('/api/game-data/items', { params: { ...filters, page: current, perPage: pageSize } });
}

export async function getItem(id: number, query: ItemQuery) {
  return request<{ data: GameDataItem }>(`/api/game-data/items/${id}`, { params: query });
}
