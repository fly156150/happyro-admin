import {
  cleanup,
  fireEvent,
  render,
  screen,
  waitFor,
} from '@testing-library/react';
import { App } from 'antd';
import { afterEach, expect, it, vi } from 'vitest';
import messages from '@/locales/zh-CN';
import { executeGameControlCommand } from '@/services/operations/game-control';
import { listCharacters } from '@/services/players/queries';
import CharacterNavigationModal from './index';

vi.mock('@/services/players/queries', () => ({ listCharacters: vi.fn() }));
vi.mock('@umijs/max', () => ({
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
vi.mock('@/services/operations/game-control', () => ({
  executeGameControlCommand: vi.fn(),
}));
afterEach(() => {
  cleanup();
  vi.clearAllMocks();
});

it('translates the Airship catalog name using the client map translation', () => {
  render(
    <App>
      <CharacterNavigationModal
        map="airplane"
        mapName="Airship"
        name="测试 NPC"
        x={31}
        y={77}
        npcClass={83}
      />
    </App>,
  );
  fireEvent.click(screen.getByRole('button', { name: '位置操作' }));
  expect(
    screen.getByText('目标位置：飞空艇 (31, 77) 的 NPC 附近。'),
  ).toBeInTheDocument();
  expect(screen.queryByText(/Airship|传送至/)).toBeNull();
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
        mapName="普隆德拉"
        name="测试 NPC"
        x={150}
        y={180}
        npcClass={83}
      />
    </App>,
  );
  fireEvent.click(screen.getByRole('button', { name: '位置操作' }));
  expect(
    screen.getByText('目标位置：普隆德拉 (150, 180) 的 NPC 附近。'),
  ).toBeInTheDocument();
  expect(screen.queryByRole('combobox', { name: '操作' })).toBeNull();
  fireEvent.change(screen.getByRole('combobox', { name: '在线目标角色' }), {
    target: { value: 'tester' },
  });
  fireEvent.click(await screen.findByText('测试在线角色 · tester'));
  expect(screen.queryByText('离线角色 · tester')).toBeNull();
  fireEvent.click(screen.getByRole('button', { name: '确认传送' }));
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
