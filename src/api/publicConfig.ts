import { request } from '@umijs/max';

export function publicConfiguration() {
  return request<{ timeZone: string }>('/api/public-config', { method: 'GET' });
}
