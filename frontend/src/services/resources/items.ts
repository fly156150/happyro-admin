import { request } from '@umijs/max';

export type GameItem = {
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
};

export function itemName(item: GameItem, locale: string): string {
  return item.names[locale] || item.names['en-US'] || Object.values(item.names)[0] || String(item.Id);
}

export async function listItems(params: Record<string, unknown>) {
  const { current, pageSize, ...filters } = params;
  return request<{ data: GameItem[]; total: number }>('/api/resources/items', { params: { ...filters, page: current, perPage: pageSize } });
}

export async function getItem(id: number) {
  return request<{ data: GameItem }>(`/api/resources/items/${id}`);
}
