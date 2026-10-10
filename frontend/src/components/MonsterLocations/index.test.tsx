import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, expect, it, vi } from 'vitest';
import MonsterLocations from './index';

const access = vi.hoisted(() => ({
  canGameControl: true,
  canViewPlayers: true,
}));
vi.mock('@umijs/max', () => ({
  useAccess: () => access,
  useIntl: () => ({
    formatMessage: ({ defaultMessage }: { defaultMessage: string }) =>
      defaultMessage,
  }),
}));
vi.mock('@/components/CharacterNavigationModal', () => ({
  default: ({ map, x, y }: { map: string; x: number; y: number }) => (
    <button type="button">
      {map} ({x}, {y})
    </button>
  ),
}));
afterEach(() => {
  cleanup();
  access.canGameControl = true;
  access.canViewPlayers = true;
});

it('offers navigation to the same Poring spawn coordinates as adventure tools', () => {
  render(<MonsterLocations monsterId={1002} />);
  expect(
    screen.getByRole('button', { name: 'prt_fild08 (94, 335)' }),
  ).toBeInTheDocument();
  expect(screen.getByText('87 只')).toBeInTheDocument();
});

it.each([
  'canGameControl',
  'canViewPlayers',
] as const)('keeps locations read-only without %s', (permission) => {
  access[permission] = false;
  render(<MonsterLocations monsterId={1002} />);
  expect(screen.getByText('87 只')).toBeInTheDocument();
  expect(screen.queryByRole('button')).not.toBeInTheDocument();
});

it('explains when no permanent spawn location is available', () => {
  render(<MonsterLocations monsterId={-1} />);
  expect(screen.getByText('暂无常驻刷新地图')).toBeInTheDocument();
});
