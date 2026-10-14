import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, expect, it } from 'vitest';
import type { ItemDropSource } from '@/services/game-data/items';
import ItemDropSources, { dropRows } from './ItemDropSources';

afterEach(cleanup);
const sources: ItemDropSource[] = [
  {
    id: 1002,
    name: '波利',
    level: 1,
    kind: 'normal',
    drops: [{ method: 'normal', rate: 100, count: 1 }],
  },
  {
    id: 1001,
    name: '测试首领',
    level: 80,
    kind: 'mvp',
    drops: [
      { method: 'normal', rate: 500, count: 2 },
      { method: 'mvp_reward', rate: 10000, count: 1 },
    ],
  },
];
it('sorts normal drops by descending base rate without mixing MVP rewards', () => {
  expect(
    dropRows(sources, 'normal').map((row) => [row.id, row.rate, row.count]),
  ).toEqual([
    [1001, 500, 2],
    [1002, 100, 1],
  ]);
  expect(dropRows(sources, 'mvp_reward').map((row) => row.id)).toEqual([1001]);
});
it('renders separate reward tables, rates and an explicit empty state', () => {
  const { rerender } = render(<ItemDropSources sources={sources} />);
  expect(screen.getByRole('heading', { name: '普通掉落' })).toBeInTheDocument();
  expect(screen.getByRole('heading', { name: 'MVP 奖励' })).toBeInTheDocument();
  expect(screen.getByText('5%')).toBeInTheDocument();
  expect(screen.getByText('100%')).toBeInTheDocument();
  rerender(<ItemDropSources sources={[]} />);
  expect(screen.getByText('无掉落信息')).toBeInTheDocument();
});
