import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { App } from 'antd';
import { afterEach, expect, it, vi } from 'vitest';
import { AvatarDropdown } from './AvatarDropdown';

const state = vi.hoisted(() => ({
  enabled: true,
  set: vi.fn(),
  replace: vi.fn(),
}));
vi.mock('@umijs/max', () => ({
  history: { replace: state.replace },
  useModel: () => ({
    initialState: {
      currentUser: { id: 1, passwordChangeEnabled: state.enabled },
    },
    setInitialState: state.set,
  }),
}));
vi.mock('@/api/auth/index', () => ({ logout: vi.fn(), currentUser: vi.fn() }));
vi.mock('../HeaderDropdown', () => ({
  default: ({
    menu,
  }: {
    menu: {
      items: { key: string; label: string }[];
      onClick: (event: { key: string }) => void;
    };
  }) => (
    <div>
      {menu.items.map((item) => (
        <button
          type="button"
          key={item.key}
          onClick={() => menu.onClick({ key: item.key })}
        >
          {item.label}
        </button>
      ))}
    </div>
  ),
}));
vi.mock('./ChangePasswordModal', () => ({
  default: ({ open }: { open: boolean }) => (open ? <div>改密表单</div> : null),
}));
afterEach(cleanup);
it('hides self-password controls when the server disables them', () => {
  state.enabled = false;
  render(
    <App>
      <AvatarDropdown />
    </App>,
  );
  expect(screen.queryByText('修改密码')).not.toBeInTheDocument();
  expect(screen.getByText('退出登录')).toBeInTheDocument();
});
it('opens the self-password form when enabled', () => {
  state.enabled = true;
  render(
    <App>
      <AvatarDropdown />
    </App>,
  );
  fireEvent.click(screen.getByText('修改密码'));
  expect(screen.getByText('改密表单')).toBeInTheDocument();
});
