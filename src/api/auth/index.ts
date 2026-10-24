import { request } from '@umijs/max';
import type { CurrentUser } from '@/api/auth/types';

export type LoginCredentials = {
  username: string;
  password: string;
  remember: boolean;
};

export type LoginResult = {
  status: 'ok';
};

export async function loginConfiguration() {
  return request<{ showDefaultCredentials: boolean }>(
    '/api/auth/configuration',
    {
      method: 'GET',
      skipErrorHandler: true,
    },
  );
}

export async function login(credentials: LoginCredentials) {
  return request<LoginResult>('/api/auth/login', {
    method: 'POST',
    data: credentials,
    skipErrorHandler: true,
  });
}

export async function currentUser(options?: { skipErrorHandler?: boolean }) {
  return request<{ data: CurrentUser }>('/api/auth/user', {
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

export async function changePassword(data: {
  currentPassword: string;
  newPassword: string;
  confirmPassword: string;
}) {
  return request<void>('/api/auth/password', {
    method: 'POST',
    data,
    skipErrorHandler: true,
  });
}
