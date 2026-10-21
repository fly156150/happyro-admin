import {
  cleanup,
  fireEvent,
  render,
  screen,
  waitFor,
} from '@testing-library/react';
import { App } from 'antd';
import { afterEach, expect, it, vi } from 'vitest';
import { listAccountDeletions, retryAccountDeletion } from '@/api/accounts';
import DeletionTasks from './DeletionTasks';

vi.mock('@umijs/max', () => ({
  useIntl: () => ({
    formatMessage: ({ defaultMessage }: { defaultMessage: string }) =>
      defaultMessage,
  }),
}));
vi.mock('@/api/accounts', () => ({
  listAccountDeletions: vi.fn(),
  retryAccountDeletion: vi.fn(),
}));
// Exercise the page's request/render/retry contract without ProTable's layout machinery.
vi.mock('@ant-design/pro-components', async () => {
  const { useEffect, useImperativeHandle, useState } = await import('react');
  return {
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
            <div key={row.account_id}>
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
  vi.clearAllMocks();
});

it('loads durable jobs on mount and refreshes a failed job after retry without claiming completion', async () => {
  const failed = {
    account_id: 2000000,
    account_name: 'player_one',
    status: 'failed' as const,
    error_code: 'character_cleanup_failed',
    total_characters: 2,
    deleted_characters: 1,
    created_at: '',
    updated_at: '',
  };
  vi.mocked(listAccountDeletions)
    .mockResolvedValueOnce({ data: [failed], total: 1 })
    .mockResolvedValue({
      data: [{ ...failed, status: 'queued', error_code: null }],
      total: 1,
    });
  vi.mocked(retryAccountDeletion).mockResolvedValue({
    data: { ...failed, status: 'queued', error_code: null },
  });
  render(
    <App>
      <DeletionTasks revision={0} />
    </App>,
  );
  expect(await screen.findByText('删除失败')).toBeTruthy();
  expect(screen.getByText('1 / 2')).toBeTruthy();
  fireEvent.click(screen.getByRole('button', { name: '重试删除' }));
  await waitFor(() =>
    expect(retryAccountDeletion).toHaveBeenCalledWith(2000000),
  );
  expect(await screen.findByText('等待处理')).toBeTruthy();
  expect(screen.queryByText('删除完成')).toBeNull();
  expect(screen.queryByRole('button', { name: '重试删除' })).toBeNull();
});

it('refreshes persisted progress after a new request and never offers retry for a running job', async () => {
  const job = {
    account_id: 2000000,
    account_name: 'player_one',
    status: 'disconnecting' as const,
    error_code: null,
    total_characters: 0,
    deleted_characters: 0,
    created_at: '',
    updated_at: '',
  };
  vi.mocked(listAccountDeletions)
    .mockResolvedValueOnce({ data: [job], total: 1 })
    .mockResolvedValue({
      data: [
        {
          ...job,
          status: 'completed',
          total_characters: 2,
          deleted_characters: 2,
        },
      ],
      total: 1,
    });
  const onCompleted = vi.fn();
  const view = render(
    <App>
      <DeletionTasks revision={0} onCompleted={onCompleted} />
    </App>,
  );
  expect(await screen.findByText('等待下线与存档')).toBeTruthy();
  expect(screen.queryByRole('button', { name: '重试删除' })).toBeNull();
  view.rerender(
    <App>
      <DeletionTasks revision={1} onCompleted={onCompleted} />
    </App>,
  );
  expect(await screen.findByText('删除完成')).toBeTruthy();
  expect(screen.getByText('2 / 2')).toBeTruthy();
  expect(onCompleted).toHaveBeenCalledOnce();
});
