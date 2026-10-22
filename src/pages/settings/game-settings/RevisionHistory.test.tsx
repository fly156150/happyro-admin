import {
  cleanup,
  fireEvent,
  render,
  screen,
  waitFor,
} from '@testing-library/react';
import { App } from 'antd';
import { afterEach, expect, it, vi } from 'vitest';
import messages from '@/locales/zh-CN';
import {
  listSettingRevisions,
  resumeSettingRevision,
  type SettingRevision,
} from '@/api/settings/game-settings';
import GameSettingHistory from '../game-setting-history';
import RevisionHistory from './RevisionHistory';

vi.mock('@umijs/max', () => ({
  useIntl: () => ({
    formatMessage: ({ id, defaultMessage }: any) =>
      (messages as Record<string, string>)[id] || defaultMessage,
  }),
}));
vi.mock('@/api/settings/game-settings', () => ({
  listSettingRevisions: vi.fn(),
  resumeSettingRevision: vi.fn(),
}));
// Exercise the page's request/render/retry contract without ProTable's layout machinery.
vi.mock('@ant-design/pro-components', async () => {
  const { useEffect, useImperativeHandle, useState } = await import('react');
  return {
    PageContainer: ({ title, children }: any) => (
      <main>
        <h1>{title}</h1>
        {children}
      </main>
    ),
    ProTable: ({ request, columns, actionRef, params, headerTitle }: any) => {
      const [rows, setRows] = useState<any[]>([]);
      const load = async () =>
        setRows((await request({ current: 1, pageSize: 10 })).data);
      useImperativeHandle(actionRef, () => ({ reload: load }));
      useEffect(() => {
        void load();
      }, [params.revision]);
      return (
        <section>
          <h2>{headerTitle}</h2>
          {rows.map((row) => (
            <div key={row.id}>
              {columns.map((column: any, index: number) => (
                <span key={column.dataIndex ?? index}>
                  {column.render
                    ? column.render(null, row)
                    : row[column.dataIndex]}
                </span>
              ))}
            </div>
          ))}
        </section>
      );
    },
  };
});
afterEach(() => {
  cleanup();
  vi.resetAllMocks();
});
const definitions = {
  refine_chance_rate: {
    key: 'refine_chance_rate',
    minimum: 0,
    maximum: 1000000,
    source: '',
    unit: 'percent',
  },
};
const pending: SettingRevision = {
  id: 'revision-one',
  changes: { refine_chance_rate: 325 },
  status: 'published',
  requested_by: null,
  requested_game_account_id: 2000000,
  created_at: '',
};
it('loads persisted revisions after refresh and confirms by revision ID without the original request key', async () => {
  const applied = { ...pending, status: 'applied' as const };
  vi.mocked(listSettingRevisions)
    .mockResolvedValueOnce({ data: [pending], definitions, meta: { total: 1 } })
    .mockResolvedValue({ data: [applied], definitions, meta: { total: 1 } });
  vi.mocked(resumeSettingRevision).mockResolvedValue({ data: applied });
  const onApplied = vi.fn().mockResolvedValue(undefined);
  render(
    <App>
      <RevisionHistory revision={0} onApplied={onApplied} />
    </App>,
  );
  expect(await screen.findByText('等待确认生效')).toBeTruthy();
  expect(screen.getByText('游戏账号 #2000000')).toBeTruthy();
  expect(screen.getByText('精炼成功率倍率：3.25 倍')).toBeTruthy();
  fireEvent.click(screen.getByRole('button', { name: '继续确认' }));
  await waitFor(() =>
    expect(resumeSettingRevision).toHaveBeenCalledWith('revision-one'),
  );
  expect(await screen.findByText('已生效')).toBeTruthy();
  expect(onApplied).toHaveBeenCalledOnce();
  expect(screen.queryByRole('button', { name: '继续确认' })).toBeNull();
});
it('keeps an unconfirmed revision recoverable when resuming fails', async () => {
  vi.mocked(listSettingRevisions).mockResolvedValue({
    data: [pending],
    definitions,
    meta: { total: 1 },
  });
  vi.mocked(resumeSettingRevision).mockRejectedValue(
    new Error('readback unavailable'),
  );
  const onApplied = vi.fn();
  render(
    <App>
      <RevisionHistory revision={0} onApplied={onApplied} />
    </App>,
  );
  fireEvent.click(await screen.findByRole('button', { name: '继续确认' }));
  await waitFor(() => expect(listSettingRevisions).toHaveBeenCalledTimes(2));
  expect(onApplied).not.toHaveBeenCalled();
  expect(screen.queryByText('已生效')).toBeNull();
  await waitFor(() =>
    expect(screen.getByRole('button', { name: /继续确认/ })).toBeEnabled(),
  );
});

it('standalone history renders units and supports recovery without loading live game settings', async () => {
  vi.mocked(listSettingRevisions).mockResolvedValue({
    data: [pending],
    definitions,
    meta: { total: 1 },
  });
  vi.mocked(resumeSettingRevision).mockResolvedValue({
    data: { ...pending, status: 'applied' },
  });
  render(
    <App>
      <GameSettingHistory />
    </App>,
  );
  expect(await screen.findByText('精炼成功率倍率：3.25 倍')).toBeTruthy();
  expect(screen.getByRole('heading', { name: '配置修改记录' })).toBeTruthy();
  fireEvent.click(screen.getByRole('button', { name: '继续确认' }));
  await waitFor(() =>
    expect(resumeSettingRevision).toHaveBeenCalledWith('revision-one'),
  );
  await waitFor(() => expect(listSettingRevisions).toHaveBeenCalledTimes(2));
});
