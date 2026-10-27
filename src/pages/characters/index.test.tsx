import { cleanup, render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, expect, it, vi } from 'vitest';
import messages from '@/locales/zh-CN';
import Characters from './index';

const { rows } = vi.hoisted(() => ({ rows: [] as Record<string, unknown>[] }));
vi.mock('@umijs/max', () => ({
  useAccess: () => ({}),
  useIntl: () => ({
    formatMessage: ({
      id,
      defaultMessage,
    }: {
      id: string;
      defaultMessage: string;
    }) => (messages as Record<string, string>)[id] ?? defaultMessage,
  }),
}));
vi.mock('@ant-design/pro-components', () => ({
  PageContainer: ({ children }: { children: ReactNode }) => children,
  ProTable: ({
    columns,
  }: {
    columns: {
      dataIndex?: string;
      render?: (value: unknown, row: Record<string, unknown>) => ReactNode;
    }[];
  }) => (
    <div>
      {rows.map((row, index) => (
        <div key={String(row.char_id ?? index)}>
          {columns
            .filter((column) =>
              ['class', 'last_map'].includes(column.dataIndex ?? ''),
            )
            .map((column) => (
              <span key={column.dataIndex}>{column.render?.(null, row)}</span>
            ))}
        </div>
      ))}
    </div>
  ),
}));
vi.mock('@/api/characters/queries', () => ({
  listCharacters: vi.fn(),
  getCharacter: vi.fn(),
}));
vi.mock('@/features/characters/CharacterMaintenanceModal', () => ({
  default: () => null,
}));
vi.mock('@/features/characters/CharacterDetails', () => ({
  default: () => null,
}));
afterEach(() => {
  cleanup();
  rows.length = 0;
});

it('renders catalog map names and expanded job names, preserving unknown map codes', () => {
  rows.push(
    { class: 4211, last_map: 'new_1-1', last_map_name: '初心者修炼场' },
    { class: 4240, last_map: 'wolfvill', last_map_name: '灰狼村' },
    { class: 4215, last_map: 'unlisted', last_map_name: null },
  );
  render(<Characters />);
  for (const name of [
    '初心者修炼场',
    '灰狼村',
    'unlisted',
    '影狼',
    '猎灵士',
    '反叛者',
  ]) {
    expect(screen.getByText(name)).toBeInTheDocument();
  }
  expect(screen.queryByText('未知地图')).not.toBeInTheDocument();
  expect(screen.queryByText('其他职业')).not.toBeInTheDocument();
});
