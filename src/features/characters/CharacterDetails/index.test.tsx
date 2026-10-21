import { cleanup, render, screen, within } from '@testing-library/react';
import { afterEach, expect, it, vi } from 'vitest';
import messages from '@/locales/zh-CN';
import CharacterDetails from './index';

vi.mock('@umijs/max', () => ({
  useIntl: () => ({
    locale: 'zh-CN',
    formatMessage: ({
      id,
      defaultMessage,
    }: {
      id: string;
      defaultMessage: string;
    }) => (messages as Record<string, string>)[id] ?? defaultMessage,
  }),
}));
afterEach(cleanup);

it('renders one grouped table with localized labels, zero values and readable numbers', () => {
  render(
    <CharacterDetails
      character={{
        char_id: 42,
        class: 7,
        online: 0,
        status_point: 0,
        zeny: 1234567,
        last_map: 'prontera',
      }}
    />,
  );
  const table = screen.getByRole('table', { name: '角色详情' });
  expect(screen.getAllByRole('table')).toHaveLength(1);
  expect(within(table).getByText('基础信息')).toBeInTheDocument();
  expect(within(table).getByText('等级与点数')).toBeInTheDocument();
  expect(within(table).getByText('基础属性')).toBeInTheDocument();
  expect(within(table).getByText('离线')).toBeInTheDocument();
  expect(within(table).getByText('1,234,567')).toBeInTheDocument();
  expect(within(table).getByText('0')).toBeInTheDocument();
  expect(within(table).getByText(/prontera/)).toBeInTheDocument();
  expect(within(table).getAllByText('—').length).toBeGreaterThan(0);
});

it('shows the full map name and code, fourth-job traits and zero AP', () => {
  render(
    <CharacterDetails
      character={{
        class: 4252,
        last_map_name: '灰狼村',
        last_map: 'wolfvill',
        pow: 100,
        sta: 80,
        wis: 60,
        spl: 40,
        con: 20,
        crt: 0,
        trait_point: 15,
        ap: 0,
        max_ap: 200,
      }}
    />,
  );
  expect(screen.getByText('灰狼村')).toBeInTheDocument();
  expect(screen.getByText('wolfvill')).toBeInTheDocument();
  expect(screen.getByText('四转特性')).toBeInTheDocument();
  for (const label of [
    '力量 POW',
    '耐力 STA',
    '智慧 WIS',
    '法力 SPL',
    '专注 CON',
    '创造 CRT',
    '剩余特性点',
    '当前 AP',
    '最大 AP',
  ]) {
    expect(
      screen.getByRole('rowheader', { name: label }),
    ).toBeInTheDocument();
  }
  expect(screen.getAllByText('0')).toHaveLength(2);
  expect(screen.getByText('200')).toBeInTheDocument();
});
