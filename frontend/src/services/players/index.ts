import { request } from '@umijs/max';

export type PlayerAccount = {
  account_id: number;
  username: string;
  email?: string;
  sex?: string;
  group_id?: number;
  state?: number;
  lastlogin?: string;
  last_ip?: string;
};

export async function listAccounts(params: Record<string, unknown>) {
  const { current, pageSize, ...filters } = params;
  return request<{ data: PlayerAccount[]; total: number }>('/api/players/accounts', {
    params: { ...filters, page: current, perPage: pageSize },
  });
}

export async function updateAccount(id: number, data: Record<string, unknown>) {
  return request(`/api/players/accounts/${id}`, { method: 'PATCH', data });
}

export async function resetPassword(id: number, password: string) {
  return request(`/api/players/accounts/${id}/password`, { method: 'POST', data: { password } });
}

export async function registerAccount(data: Record<string, unknown>) {
  return request('/api/players/accounts', { method: 'POST', data });
}

export async function deleteAccount(id: number) { return request(`/api/players/accounts/${id}`, { method: 'DELETE' }); }
export async function batchAccounts(action: 'delete' | 'ban' | 'unban' | 'kick', accountIds: number[]) { return request<{ count: number }>('/api/players/accounts/batch', { method: 'POST', data: { action, account_ids: accountIds } }); }
