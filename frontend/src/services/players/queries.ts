import { request } from '@umijs/max';

function queryParams(params: Record<string, unknown>) {
  const { current, pageSize, ...filters } = params;
  return { ...filters, page: current, perPage: pageSize };
}

export async function listCharacters(params: Record<string, unknown>) { return request<{ data: Record<string, unknown>[]; total: number }>('/api/players/characters', { params: queryParams(params) }); }
export async function listLoginLogs(params: Record<string, unknown>) { return request<{ data: Record<string, unknown>[]; total: number }>('/api/players/login-logs', { params: queryParams(params) }); }
