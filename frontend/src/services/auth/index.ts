import { request } from '@umijs/max';

export type LoginCredentials = {
  username: string;
  password: string;
  remember: boolean;
};

export type LoginResult = {
  status: 'ok';
};

export async function login(credentials: LoginCredentials) {
  await request('/sanctum/csrf-cookie', {
    method: 'GET',
    skipErrorHandler: true,
  });

  return request<LoginResult>('/api/auth/login', {
    method: 'POST',
    data: credentials,
    skipErrorHandler: true,
  });
}

export async function currentUser(options?: { skipErrorHandler?: boolean }) {
  return request<{ data: API.CurrentUser }>('/api/auth/user', {
    method: 'GET',
    ...options,
  });
}

export async function logout() {
  return request<void>('/api/auth/logout', {
    method: 'POST',
    skipErrorHandler: true,
  });
}
