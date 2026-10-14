import { act, cleanup, render, waitFor } from '@testing-library/react';
import { afterEach, expect, it, vi } from 'vitest';
import { listItems } from '@/services/game-data/items';
import Items from './index';

const state = vi.hoisted(() => ({ table: null as any }));
vi.mock('@ant-design/pro-components', () => ({
  PageContainer: ({ children }: any) => <div>{children}</div>,
  ProTable: (props: any) => {
    state.table = props;
    return null;
  },
}));
vi.mock('@umijs/max', () => ({
  useAccess: () => ({ canGrantItems: false }),
  useIntl: () => ({
    locale: 'zh-CN',
    formatMessage: ({ defaultMessage }: any) => defaultMessage,
  }),
}));
vi.mock('@/components/ItemGrantModal', () => ({ default: () => null }));
vi.mock('@/services/game-data/items', () => ({
  listItems: vi.fn(),
  getItem: vi.fn(),
  itemName: () => '',
}));
afterEach(() => {
  cleanup();
  vi.resetAllMocks();
});

it('uses available subtypes, clears old options on type changes and sends source filters', async () => {
  vi.mocked(listItems).mockResolvedValue({
    data: [],
    total: 0,
    subtypes: ['Enchant', 'Weapon'],
  });
  render(<Items />);
  expect(
    state.table.columns.find((c: any) => c.dataIndex === 'dropKind').valueEnum,
  ).toEqual({ all: '全部来源', normal: '普通魔物', mvp: 'MVP', mini: 'Mini' });
  act(() => state.table.form.onValuesChange({ type: 'Card' }));
  await waitFor(() =>
    expect(
      state.table.columns.find((c: any) => c.dataIndex === 'subtype'),
    ).toBeDefined(),
  );
  expect(
    Object.keys(
      state.table.columns.find((c: any) => c.dataIndex === 'subtype').valueEnum,
    ),
  ).toEqual(['Enchant', 'Weapon']);
  await state.table.request({
    type: 'Card',
    dropKind: 'mvp',
    current: 1,
    pageSize: 20,
  });
  expect(listItems).toHaveBeenLastCalledWith({
    type: 'Card',
    dropKind: 'mvp',
    current: 1,
    pageSize: 20,
  });
  vi.mocked(listItems).mockResolvedValue({ data: [], total: 0, subtypes: [] });
  act(() => state.table.form.onValuesChange({ type: 'Healing' }));
  await waitFor(() =>
    expect(
      state.table.columns.find((c: any) => c.dataIndex === 'subtype'),
    ).toBeUndefined(),
  );
});
