import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { expect, it, vi } from 'vitest';
import { listLoginLogs } from '@/api/accounts/login-logs';
import LoginLogs from './index';

vi.mock('@umijs/max', () => ({
  useIntl: () => ({
    formatMessage: ({ defaultMessage }: { defaultMessage: string }) =>
      defaultMessage,
  }),
}));
vi.mock('@/api/accounts/login-logs', () => ({ listLoginLogs: vi.fn() }));
vi.mock('@ant-design/pro-components', async () => {
  const { useEffect, useState } = await import('react');
  const { Table } = await import('antd');
  return {
    PageContainer: ({ children }: any) => children,
    ProTable: ({ request, ...props }: any) => {
      const [rows, setRows] = useState<any[]>([]);
      const load = async () =>
        setRows((await request({ current: 1, pageSize: 20 })).data);
      useEffect(() => {
        void load();
      }, []);
      return (
        <>
          <button type="button" onClick={load}>Search</button>
          <Table {...props} dataSource={rows} pagination={false} />
        </>
      );
    },
  };
});

it('removes all same-second duplicate login rows when a filter returns no results', async () => {
  const row = {
    time: '2026-10-22 10:00:00',
    username: 'duplicate_player',
    ip: '127.0.0.1',
    rcode: 100,
    log: 'login ok',
  };
  vi.mocked(listLoginLogs)
    .mockResolvedValueOnce({ data: [row, row, row], total: 3 })
    .mockResolvedValue({ data: [], total: 0 });
  render(<LoginLogs />);
  await waitFor(() =>
    expect(screen.getAllByText('duplicate_player')).toHaveLength(3),
  );
  fireEvent.click(screen.getByText('Search'));
  await waitFor(() =>
    expect(screen.queryAllByText('duplicate_player')).toHaveLength(0),
  );
});
