import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { request } from '@umijs/max';
import { expect, it, vi } from 'vitest';
import ItemGrantRecords from './index';

vi.mock('@umijs/max', () => ({
  request: vi.fn().mockResolvedValue({ data: [], meta: { total: 200 } }),
  useIntl: () => ({ formatMessage: ({ defaultMessage }: { defaultMessage: string }) => defaultMessage }),
}));
vi.mock('@ant-design/pro-components', () => ({
  PageContainer: ({ children }: any) => children,
  ProTable: ({ request: load }: any) => (
    <button type="button" onClick={() => load({ current: 2, pageSize: 50 })}>
      Load second page of 50
    </button>
  ),
}));

it('sends the selected page and page size through to the grant history API', async () => {
  render(<ItemGrantRecords />);
  fireEvent.click(screen.getByText('Load second page of 50'));
  await waitFor(() => expect(request).toHaveBeenCalledWith(
    '/api/operations/item-grants',
    { params: { page: 2, per_page: 50 } },
  ));
});
