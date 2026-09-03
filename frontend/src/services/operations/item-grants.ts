import { request } from '@umijs/max';

export type ItemGrant = {
  item_id: number;
  char_id: number;
  amount: number;
  title: string;
  message: string;
  bound?: boolean;
};

export type ItemGrantTarget = {
  char_id: number;
  name: string;
  username: string;
};

export type ItemGrantItem = {
  item_id: number;
  aegis_name?: string;
  names: Record<string, string>;
};

export async function searchItemGrantItems(target: string) {
  return request<{ data: ItemGrantItem[]; success: boolean }>(
    '/api/operations/item-grant-items',
    { params: { target } },
  );
}

export async function searchItemGrantTargets(target: string) {
  return request<{ data: ItemGrantTarget[]; success: boolean }>(
    '/api/operations/item-grant-targets',
    { params: { target } },
  );
}

export async function mailItem(data: ItemGrant) {
  return request('/api/operations/item-grants/mail', { method: 'POST', data });
}
