import { cleanup, render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, expect, it, vi } from 'vitest';
import type { MapRow } from '@/api/catalog/maps';
import Maps from './index';

const { rows } = vi.hoisted(() => ({ rows: [] as MapRow[] }));
vi.mock('@umijs/max', () => ({
  useAccess: () => ({ canGameControl: true, canViewPlayers: true }),
  useIntl: () => ({
    formatMessage: ({ defaultMessage }: { defaultMessage: string }) =>
      defaultMessage,
  }),
}));
vi.mock('@ant-design/pro-components', () => ({
  PageContainer: ({ children }: { children: ReactNode }) => children,
  ProTable: ({
    columns,
  }: {
    columns: {
      valueType?: string;
      render?: (value: unknown, row: MapRow) => ReactNode;
    }[];
  }) => (
    <div>
      {rows.map((row) => (
        <div key={row.map}>
          <span>{row.map}</span>
          {columns
            .find((column) => column.valueType === 'option')
            ?.render?.(null, row)}
        </div>
      ))}
    </div>
  ),
}));
vi.mock('@/api/catalog/maps', () => ({ listMaps: vi.fn() }));
vi.mock('@/api/catalog/npcs', () => ({ listMapNpcs: vi.fn() }));
vi.mock('@/features/characters/CharacterNavigationModal', () => ({
  default: ({ map }: { map: string }) => (
    <button type="button">传送到 {map}</button>
  ),
}));
afterEach(() => {
  cleanup();
  rows.length = 0;
});

it('keeps resource maps inspectable while hiding their teleport actions', () => {
  for (let i = 1; i <= 4; i++) {
    rows.push({
      id: i,
      map: `new_zone0${i}`,
      supported: true,
      player_hidden: true,
    });
    rows.push({
      id: i + 4,
      map: `new_1-${i}`,
      supported: true,
      player_hidden: false,
    });
  }
  render(<Maps />);
  expect(screen.getAllByRole('button', { name: /详情/ })).toHaveLength(8);
  for (let i = 1; i <= 4; i++) {
    expect(screen.getByText(`new_zone0${i}`)).toBeInTheDocument();
    expect(
      screen.queryByRole('button', { name: `传送到 new_zone0${i}` }),
    ).not.toBeInTheDocument();
    expect(
      screen.getByRole('button', { name: `传送到 new_1-${i}` }),
    ).toBeInTheDocument();
  }
});
