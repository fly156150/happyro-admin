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
  getGameSettings,
  updateGameSettings,
} from '@/api/settings/game-settings';
import messages from '@/locales/zh-CN';
import GameSettingsPage from './index';

vi.mock('./RevisionHistory', () => ({
  default: () => <div>嵌入的修改记录</div>,
}));

vi.mock('@umijs/max', () => ({
  useIntl: () => ({
    formatMessage: ({ id, defaultMessage }: any) =>
      (messages as Record<string, string>)[id] || defaultMessage,
  }),
}));
vi.mock('@/api/settings/game-settings', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@/api/settings/game-settings')>()),
  getGameSettings: vi.fn(),
  updateGameSettings: vi.fn(),
}));
// Exercise real Form state/validation; adapt ProForm wrappers to the Node test environment.
vi.mock('@ant-design/pro-components', async () => {
  const { Form, InputNumber, Radio, Button } = await import('antd');
  const { useImperativeHandle } = await import('react');
  return {
    PageContainer: ({ children, extra }: any) => (
      <div>
        {extra}
        {children}
      </div>
    ),
    ProForm: ({ formRef, submitter: _submitter, children, ...props }: any) => {
      const [form] = Form.useForm();
      useImperativeHandle(formRef, () => form);
      return (
        <Form form={form} {...props}>
          {children}
          <Button htmlType="submit">保存</Button>
        </Form>
      );
    },
    ProFormDigit: ({
      fieldProps,
      width: _width,
      addonAfter: _addon,
      formItemProps,
      disabled,
      ...props
    }: any) => (
      <Form.Item {...props} {...formItemProps}>
        <InputNumber {...fieldProps} disabled={disabled} />
      </Form.Item>
    ),
    ProFormRadio: {
      Group: ({ options, radioType: _radio, ...props }: any) => (
        <Form.Item {...props}>
          <Radio.Group options={options} />
        </Form.Item>
      ),
    },
  };
});
afterEach(() => {
  cleanup();
  vi.clearAllMocks();
});
it('loads shared refinement settings, disables the multiplier and saves retained values', async () => {
  const values = {
    base_exp_rate: 100,
    job_exp_rate: 100,
    refine_chance_rate: 250,
    refine_always_success: 0,
  };
  const definitions = Object.fromEntries(
    Object.keys(values).map((key) => [
      key,
      {
        key,
        minimum: 0,
        maximum: key === 'refine_always_success' ? 1 : 1000000,
        source: 'conf/battle/items.conf',
        unit: key === 'refine_always_success' ? 'boolean' : 'percent',
      },
    ]),
  );
  vi.mocked(getGameSettings).mockResolvedValue({
    data: { values, definitions },
  });
  vi.mocked(updateGameSettings).mockImplementation(async ({ changes }) => {
    Object.assign(values, changes);
    return { data: {} };
  });
  render(
    <App>
      <GameSettingsPage />
    </App>,
  );
  expect(screen.queryByText('嵌入的修改记录')).toBeNull();
  expect(screen.getByRole('link', { name: /查看修改记录/ })).toHaveAttribute(
    'href',
    '/settings/game-setting-history',
  );
  fireEvent.click(await screen.findByRole('tab', { name: '装备精炼' }));
  const rate = await screen.findByRole('spinbutton', {
    name: '精炼成功率倍率',
  });
  expect(rate).toHaveValue('2.5');
  fireEvent.change(rate, { target: { value: '3.25' } });
  fireEvent.blur(rate);
  fireEvent.click(screen.getByRole('radio', { name: '开启' }));
  await waitFor(() => expect(rate).toBeDisabled());
  fireEvent.click(screen.getByRole('button', { name: /保\s*存/ }));
  await waitFor(() =>
    expect(updateGameSettings).toHaveBeenCalledWith({
      idempotency_key: expect.any(String),
      changes: expect.objectContaining({
        refine_chance_rate: 325,
        refine_always_success: 1,
      }),
    }),
  );
  await waitFor(() => expect(getGameSettings).toHaveBeenCalledTimes(2));
  fireEvent.click(screen.getByRole('radio', { name: '关闭' }));
  await waitFor(() => expect(rate).not.toBeDisabled());
  expect(rate).toHaveValue('3.25');
  fireEvent.click(screen.getByRole('button', { name: /保\s*存/ }));
  await waitFor(() =>
    expect(updateGameSettings).toHaveBeenLastCalledWith({
      idempotency_key: expect.any(String),
      changes: expect.objectContaining({
        refine_chance_rate: 325,
        refine_always_success: 0,
      }),
    }),
  );
});
