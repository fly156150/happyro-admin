import {
  cleanup,
  fireEvent,
  render,
  screen,
  waitFor,
} from '@testing-library/react';
import { App } from 'antd';
import { afterEach, expect, it, vi } from 'vitest';
import {
  getRegistrationSettings,
  updateRegistrationSettings,
} from '@/api/settings/registration';
import messages from '@/locales/zh-CN';
import RegistrationSettings from './index';

vi.mock('@umijs/max', () => ({
  useIntl: () => ({
    formatMessage: ({ id }: { id: string }) =>
      (messages as Record<string, string>)[id] ?? id,
  }),
}));
vi.mock('@/api/settings/registration', () => ({
  getRegistrationSettings: vi.fn(),
  updateRegistrationSettings: vi.fn(),
}));
afterEach(() => {
  cleanup();
  vi.resetAllMocks();
});

it('disables both group selection and save when the environment locks edits', async () => {
  vi.mocked(getRegistrationSettings).mockResolvedValue({
    data: { default_group_id: 99, editable: false },
  });
  render(
    <App>
      <RegistrationSettings />
    </App>,
  );
  expect(
    await screen.findByRole('combobox', { name: '新注册账号默认权限组' }),
  ).toBeDisabled();
  expect(screen.getByText('GM')).toBeInTheDocument();
  expect(screen.getByRole('button', { name: '保 存' })).toBeDisabled();
  expect(updateRegistrationSettings).not.toHaveBeenCalled();
});

it('saves a chosen group with the previous value and reloads the persisted setting', async () => {
  vi.mocked(getRegistrationSettings)
    .mockResolvedValueOnce({ data: { default_group_id: 0, editable: true } })
    .mockResolvedValue({ data: { default_group_id: 99, editable: true } });
  vi.mocked(updateRegistrationSettings).mockResolvedValue({
    data: { default_group_id: 99 },
  });
  render(
    <App>
      <RegistrationSettings />
    </App>,
  );
  const select = await screen.findByRole('combobox', {
    name: '新注册账号默认权限组',
  });
  fireEvent.mouseDown(select);
  fireEvent.click(await screen.findByText('GM'));
  fireEvent.click(screen.getByRole('button', { name: '保 存' }));
  await waitFor(() =>
    expect(updateRegistrationSettings).toHaveBeenCalledWith(99, 0),
  );
  await waitFor(() => expect(getRegistrationSettings).toHaveBeenCalledTimes(2));
  expect(await screen.findByText('注册设置已保存')).toBeInTheDocument();
});
