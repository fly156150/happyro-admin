import { request } from '@umijs/max';

export type ItemGrant = {
  idempotency_key: string;
  item_id: number;
  char_id: number;
  amount: number;
  title?: string;
  message?: string;
  bound?: boolean;
  delivery?: 'mail' | 'inventory';
};

export async function listItemGrants(page = 1) {
  return request<{
    data: Array<Record<string, unknown>>;
    meta: { current_page: number; last_page: number; total: number };
    success: boolean;
  }>(`/api/operations/item-grants?page=${page}`);
}

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

export async function grantItem(data: ItemGrant) {
  return request('/api/operations/item-grants/mail', { method: 'POST', data });
}

export const mailItem = grantItem;

export async function grantZeny(data: {
  idempotency_key: string;
  char_id: number;
  amount: number;
}) {
  return request('/api/operations/zeny-grants', { method: 'POST', data });
}
