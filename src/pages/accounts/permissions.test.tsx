import { cleanup, render, screen } from '@testing-library/react';
import { App } from 'antd';
import { afterEach, expect, it, vi } from 'vitest';
import access from '@/access';
import type { CurrentUser } from '@/api/auth/types';
import Accounts from './index';

const state = vi.hoisted(() => ({ canEditPlayers: false }));
vi.mock('@umijs/max', () => ({
  useAccess: () => state,
  useIntl: () => ({
    formatMessage: ({ defaultMessage }: { defaultMessage: string }) =>
      defaultMessage,
  }),
}));
vi.mock('@/api/accounts', () => ({
  batchAccounts: vi.fn(),
  deleteAccount: vi.fn(),
  listAccounts: vi.fn(),
  registerAccount: vi.fn(),
  resetPassword: vi.fn(),
  updateAccount: vi.fn(),
}));
vi.mock('./DeletionTasks', () => ({ default: () => <div>删除任务列表</div> }));
vi.mock('@ant-design/pro-components', () => ({
  PageContainer: ({ children }: any) => <main>{children}</main>,
  ProTable: ({ headerTitle, columns, rowSelection }: any) => (
    <section>
      {headerTitle}
      {rowSelection && <input type="checkbox" aria-label="选择账号" />}
      {columns
        .filter(
          (column: any) => !column.hideInTable && column.valueType === 'option',
        )
        .map((column: any) => (
          <div key={column.title}>
            {column.render(
              null,
              { account_id: 2000000, username: 'player_one', state: 0 },
              null,
              {},
            )}
          </div>
        ))}
    </section>
  ),
}));
afterEach(() => cleanup());

it('requires both view and edit permissions for account management', () => {
  const flags = (permissions: string[]) =>
    access({ currentUser: { permissions } as CurrentUser });
  expect(flags(['players.view']).canEditPlayers).toBe(false);
  expect(flags(['players.edit']).canEditPlayers).toBe(false);
  expect(flags(['players.view', 'players.edit']).canEditPlayers).toBe(true);
  expect(flags(['*']).canEditPlayers).toBe(true);
  expect(access(undefined).canEditPlayers).toBe(false);
  expect(flags(['game-data.view']).canViewGameData).toBe(true);
  expect(flags(['players.view']).canViewGameData).toBe(false);
});

it('hides write actions, selection and deletion tasks from a read-only operator', () => {
  state.canEditPlayers = false;
  render(
    <App>
      <Accounts />
    </App>,
  );
  for (const name of ['注册账号', '重置密码', '封禁', '删除']) {
    expect(screen.queryByRole('button', { name: new RegExp(name) })).toBeNull();
  }
  expect(screen.queryByRole('checkbox')).toBeNull();
  expect(screen.queryByText('删除任务列表')).toBeNull();
});

it('shows account management actions to an authorized editor', () => {
  state.canEditPlayers = true;
  render(
    <App>
      <Accounts />
    </App>,
  );
  for (const name of ['注册账号', '重置密码', '封禁', '删除']) {
    expect(screen.getByRole('button', { name: new RegExp(name) })).toBeTruthy();
  }
  expect(screen.getByRole('checkbox')).toBeTruthy();
  expect(screen.getByText('删除任务列表')).toBeTruthy();
});
