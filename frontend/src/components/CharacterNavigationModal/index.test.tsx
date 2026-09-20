import { App } from 'antd';
import {
  cleanup,
  fireEvent,
  render,
  screen,
  waitFor,
} from '@testing-library/react';
import { afterEach, expect, it, vi } from 'vitest';
import CharacterNavigationModal from './index';
import { listCharacters } from '@/services/players/queries';
import { executeGameControlCommand } from '@/services/operations/game-control';

vi.mock('@/services/players/queries', () => ({ listCharacters: vi.fn() }));
vi.mock('@/services/operations/game-control', () => ({
  executeGameControlCommand: vi.fn(),
}));
afterEach(() => {
  cleanup();
  vi.clearAllMocks();
});

it('selects an online character and sends the NPC identity with fixed coordinates', async () => {
  vi.mocked(listCharacters).mockResolvedValue({
    data: [
      { char_id: 42, name: '测试在线角色', username: 'tester', online: 1 },
      { char_id: 43, name: '离线角色', username: 'tester', online: 0 },
    ],
    total: 2,
  } as Awaited<ReturnType<typeof listCharacters>>);
  vi.mocked(executeGameControlCommand).mockResolvedValue({
    data: { result: { char_id: 42 } },
  });
  render(
    <App>
      <CharacterNavigationModal
        map="prontera"
        name="测试 NPC"
        x={150}
        y={180}
        npcClass={83}
      />
    </App>,
  );
  fireEvent.click(screen.getByRole('button', { name: '位置操作' }));
  fireEvent.change(screen.getByRole('combobox', { name: '在线目标角色' }), {
    target: { value: 'tester' },
  });
  fireEvent.click(await screen.findByText('测试在线角色 · tester'));
  expect(screen.queryByText('离线角色 · tester')).toBeNull();
  fireEvent.click(screen.getByRole('button', { name: '确认执行' }));
  await waitFor(() =>
    expect(executeGameControlCommand).toHaveBeenCalledWith(
      expect.objectContaining({
        type: 'character.navigation.teleport',
        target: { type: 'character', id: '42' },
        payload: { map: 'prontera', x: 150, y: 180, npc_class: 83 },
      }),
    ),
  );
});
