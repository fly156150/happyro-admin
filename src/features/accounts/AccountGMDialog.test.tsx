import {
  cleanup,
  fireEvent,
  render,
  screen,
  waitFor,
} from '@testing-library/react';
import { App } from 'antd';
import { afterEach, expect, it, vi } from 'vitest';
import access from '@/access';
import { setAccountGM } from '@/api/accounts';
import type { CurrentUser } from '@/api/auth/types';
import AccountGMDialog from './AccountGMDialog';

vi.mock('@umijs/max', () => ({
  useIntl: () => ({
    formatMessage: ({ defaultMessage }: { defaultMessage: string }) =>
      defaultMessage,
  }),
}));
vi.mock('@/api/accounts', () => ({ setAccountGM: vi.fn() }));
afterEach(() => {
  cleanup();
  vi.clearAllMocks();
});
it('requires GM permission independently of ordinary account edits', () => {
  const flags = (permissions: string[]) =>
    access({ currentUser: { permissions } as CurrentUser });
  expect(flags(['players.view', 'players.edit']).canManageGM).toBe(false);
  expect(flags(['players.gm']).canManageGM).toBe(false);
  expect(flags(['players.view', 'players.gm']).canManageGM).toBe(true);
  expect(flags(['*']).canManageGM).toBe(true);
});
it('saves the selected account group with a stale-update guard and explains relogin', async () => {
  vi.mocked(setAccountGM).mockResolvedValue({
    data: { account_id: 2000000, username: 'player', group_id: 99 },
  });
  const onSaved = vi.fn();
  render(
    <App>
      <AccountGMDialog
        account={{ account_id: 2000000, username: 'player', group_id: 0 }}
        onSaved={onSaved}
        onClose={vi.fn()}
      />
    </App>,
  );
  expect(screen.getByText(/全部角色/)).toBeTruthy();
  expect(screen.getByText(/仅切换角色不会生效/)).toBeTruthy();
  expect(screen.getByRole('button', { name: '保存权限' })).toBeDisabled();
  fireEvent.click(screen.getByRole('radio', { name: /游戏管理员 GM/ }));
  await waitFor(() =>
    expect(screen.getByRole('button', { name: '保存权限' })).toBeEnabled(),
  );
  fireEvent.click(screen.getByRole('button', { name: '保存权限' }));
  await waitFor(() =>
    expect(setAccountGM).toHaveBeenCalledWith(2000000, 99, 0),
  );
  expect(onSaved).toHaveBeenCalledOnce();
});
