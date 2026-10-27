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

it.each([
  false,
  true,
])('keeps save clickable and reports unchanged values (editable=%s)', async (editable) => {
  vi.mocked(getRegistrationSettings).mockResolvedValue({
    data: { default_group_id: 99, editable },
  });
  render(
    <App>
      <RegistrationSettings />
    </App>,
  );
  const select = await screen.findByRole('combobox', {
    name: '注册默认权限组',
  });
  if (editable) expect(select).toBeEnabled();
  else expect(select).toBeDisabled();
  expect(screen.getByText('GM')).toBeInTheDocument();
  const save = screen.getByRole('button', { name: '保 存' });
  expect(save).toBeEnabled();
  fireEvent.click(save);
  expect(await screen.findByText('没有需要保存的修改')).toBeInTheDocument();
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
    name: '注册默认权限组',
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
