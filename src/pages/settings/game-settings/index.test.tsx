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
      changes: { refine_always_success: 0 },
    }),
  );
});

it('preserves another administrator change to an untouched setting', async () => {
  const initial = { base_exp_rate: 100, job_exp_rate: 100 };
  const live = { ...initial };
  const definitions = Object.fromEntries(
    Object.keys(initial).map((key) => [
      key,
      {
        key,
        minimum: 0,
        maximum: 1000000,
        source: 'conf/battle/exp.conf',
        unit: 'percent',
      },
    ]),
  );
  vi.mocked(getGameSettings).mockImplementation(async () => ({
    data: { values: { ...live }, definitions },
  }));
  vi.mocked(updateGameSettings).mockImplementation(async ({ changes }) => {
    Object.assign(live, changes);
    return { data: {} };
  });
  render(
    <App>
      <GameSettingsPage />
    </App>,
  );
  const rate = await screen.findByRole('spinbutton', { name: '基础倍率' });
  live.job_exp_rate = 300;
  fireEvent.change(rate, { target: { value: '2' } });
  fireEvent.blur(rate);
  fireEvent.click(screen.getByRole('button', { name: /保\s*存/ }));
  await waitFor(() => expect(updateGameSettings).toHaveBeenCalledOnce());
  expect(live).toEqual({ base_exp_rate: 200, job_exp_rate: 300 });
  expect(updateGameSettings).toHaveBeenCalledWith({
    idempotency_key: expect.any(String),
    changes: { base_exp_rate: 200 },
  });
});

it('does not publish a revision when the form has no changes', async () => {
  const values = { base_exp_rate: 100, job_exp_rate: 100 };
  const definitions = Object.fromEntries(
    Object.keys(values).map((key) => [
      key,
      {
        key,
        minimum: 0,
        maximum: 1000000,
        source: 'conf/battle/exp.conf',
        unit: 'percent',
      },
    ]),
  );
  vi.mocked(getGameSettings).mockResolvedValue({
    data: { values, definitions },
  });
  render(
    <App>
      <GameSettingsPage />
    </App>,
  );
  await screen.findByRole('spinbutton', { name: '基础倍率' });
  fireEvent.click(screen.getByRole('button', { name: /保\s*存/ }));
  await screen.findByText('没有需要保存的修改');
  expect(updateGameSettings).not.toHaveBeenCalled();
});

it('locks editing until both publication and readback finish', async () => {
  const definitions = Object.fromEntries(
    ['base_exp_rate', 'job_exp_rate'].map((key) => [
      key,
      {
        key,
        minimum: 0,
        maximum: 1000000,
        source: 'conf/battle/exp.conf',
        unit: 'percent',
      },
    ]),
  );
  let finishPublication!: (result: { data: unknown }) => void;
  let finishReadback!: (
    result: Awaited<ReturnType<typeof getGameSettings>>,
  ) => void;
  vi.mocked(getGameSettings)
    .mockResolvedValueOnce({
      data: { values: { base_exp_rate: 100, job_exp_rate: 100 }, definitions },
    })
    .mockImplementationOnce(
      () =>
        new Promise((resolve) => {
          finishReadback = resolve;
        }),
    );
  vi.mocked(updateGameSettings).mockImplementationOnce(
    () =>
      new Promise((resolve) => {
        finishPublication = resolve;
      }),
  );
  render(
    <App>
      <GameSettingsPage />
    </App>,
  );
  const rate = await screen.findByRole('spinbutton', { name: '基础倍率' });
  fireEvent.change(rate, { target: { value: '2' } });
  fireEvent.blur(rate);
  fireEvent.click(screen.getByRole('button', { name: /保\s*存/ }));
  await waitFor(() => expect(updateGameSettings).toHaveBeenCalledOnce());
  expect(rate).toBeDisabled();
  finishPublication({ data: {} });
  await waitFor(() => expect(getGameSettings).toHaveBeenCalledTimes(2));
  expect(rate).toBeDisabled();
  finishReadback({
    data: { values: { base_exp_rate: 200, job_exp_rate: 100 }, definitions },
  });
  await waitFor(() => expect(rate).not.toBeDisabled());
  expect(rate).toHaveValue('2');
});
