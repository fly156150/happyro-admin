import {
  cleanup,
  fireEvent,
  render,
  screen,
  waitFor,
  within,
} from '@testing-library/react';
import { App } from 'antd';
import { afterEach, expect, it, vi } from 'vitest';
import access from '@/access';
import { registerAccount, resetPassword } from '@/api/accounts';
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
  for (const name of ['注册账号', '修改邮箱', '重置密码', '封禁', '删除']) {
    expect(screen.queryByRole('button', { name: new RegExp(name) })).toBeNull();
  }
  expect(screen.queryByRole('checkbox')).toBeNull();
  expect(screen.queryByText('删除任务列表')).toBeNull();
});

it('groups account management actions in a menu for an authorized editor', async () => {
  state.canEditPlayers = true;
  render(
    <App>
      <Accounts />
    </App>,
  );
  expect(screen.getByRole('button', { name: /注册账号/ })).toBeTruthy();
  expect(screen.queryByRole('button', { name: /修改邮箱/ })).toBeNull();
  fireEvent.click(
    screen.getByRole('button', { name: 'player_one 的账号操作' }),
  );
  for (const name of ['修改邮箱', '重置密码', '封禁', '删除']) {
    expect(
      await screen.findByRole('menuitem', { name: new RegExp(name) }),
    ).toBeTruthy();
  }
  expect(screen.getByRole('checkbox')).toBeTruthy();
  expect(screen.getByText('删除任务列表')).toBeTruthy();
});

for (const mode of ['register', 'password'] as const) {
  it(`preserves the ${mode} form after an API rejection and accepts a corrected retry`, async () => {
    state.canEditPlayers = true;
    const send =
      mode === 'register'
        ? vi.mocked(registerAccount)
        : vi.mocked(resetPassword);
    send
      .mockRejectedValueOnce(new Error('rejected'))
      .mockResolvedValueOnce({} as never);
    render(
      <App>
        <Accounts />
      </App>,
    );
    if (mode === 'register') {
      fireEvent.click(screen.getByRole('button', { name: /注册账号/ }));
    } else {
      fireEvent.click(
        screen.getByRole('button', { name: 'player_one 的账号操作' }),
      );
      fireEvent.click(
        await screen.findByRole('menuitem', { name: /重置密码/ }),
      );
    }
    const modal = within(screen.getByRole('dialog'));
    if (mode === 'register') {
      fireEvent.change(modal.getByLabelText('用户名'), {
        target: { value: 'player_two' },
      });
      fireEvent.change(modal.getByLabelText('邮箱'), {
        target: { value: 'two@example.com' },
      });
    }
    fireEvent.change(modal.getByLabelText('新密码'), {
      target: { value: 'original-secret' },
    });
    fireEvent.click(modal.getByRole('button', { name: 'OK' }));
    await waitFor(() => expect(send).toHaveBeenCalledTimes(1));
    expect(modal.getByLabelText('新密码')).toHaveValue('original-secret');
    fireEvent.change(modal.getByLabelText('新密码'), {
      target: { value: 'corrected-secret' },
    });
    fireEvent.click(modal.getByRole('button', { name: 'OK' }));
    await waitFor(() => expect(send).toHaveBeenCalledTimes(2));
    expect(
      await screen.findByText(
        mode === 'register' ? '账号已注册' : '密码已重置',
      ),
    ).toBeInTheDocument();
  });
}
