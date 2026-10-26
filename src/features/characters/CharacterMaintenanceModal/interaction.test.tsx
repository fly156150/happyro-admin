import {
  cleanup,
  fireEvent,
  render,
  screen,
  waitFor,
} from '@testing-library/react';
import { App } from 'antd';
import { afterEach, expect, it, vi } from 'vitest';
import {
  executeGameControlCommand,
  getCharacterSnapshot,
} from '@/api/characters/game-control';
import messages from '@/locales/zh-CN';
import CharacterMaintenanceModal from './index';

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
vi.mock('@/api/characters/game-control', () => ({
  getCharacterSnapshot: vi.fn(),
  executeGameControlCommand: vi.fn(),
}));
afterEach(() => {
  cleanup();
  vi.resetAllMocks();
});

const offline = {
  online: false,
  base_level: 99,
  job_level: 50,
  status_points: 48,
  skill_points: 10,
  job_id: 7,
};

it.each([
  false,
  true,
])('shows expanded current and selectable job names (online=%s)', async (online) => {
  vi.mocked(getCharacterSnapshot).mockResolvedValue({
    data: {
      ...offline,
      online,
      job_id: 4211,
      jobs: [{ id: 4240, max_base_level: 200, max_job_level: 70 }],
    },
  });
  render(
    <App>
      <CharacterMaintenanceModal charId={42} characterName="职业验收角色" />
    </App>,
  );
  fireEvent.click(screen.getByRole('button', { name: /角色属性/ }));
  fireEvent.click(await screen.findByRole('tab', { name: '职业' }));
  expect((await screen.findAllByText('影狼')).length).toBeGreaterThan(0);
  const select = screen.getByRole('combobox', { name: '目标职业' });
  fireEvent.mouseDown(select);
  fireEvent.change(select, { target: { value: '猎灵士' } });
  expect(await screen.findByText('猎灵士')).toBeInTheDocument();
  expect(screen.queryByText('其他职业')).not.toBeInTheDocument();
  expect(executeGameControlCommand).not.toHaveBeenCalled();
});

it('opens saved offline values silently and reports offline only when a change is submitted', async () => {
  vi.mocked(getCharacterSnapshot).mockResolvedValue({ data: offline });
  render(
    <App>
      <CharacterMaintenanceModal charId={42} characterName="离线测试角色" />
    </App>,
  );
  fireEvent.click(screen.getByRole('button', { name: /角色属性/ }));
  fireEvent.click(await screen.findByRole('tab', { name: '等级与点数' }));
  await waitFor(() =>
    expect(screen.getByRole('spinbutton', { name: '基础等级' })).toHaveValue(
      '99',
    ),
  );
  expect(screen.queryByText('角色不在线。')).not.toBeInTheDocument();
  fireEvent.change(screen.getByRole('spinbutton', { name: '基础等级' }), {
    target: { value: '100' },
  });
  fireEvent.click(screen.getByRole('button', { name: '应用修改' }));
  await screen.findByText('角色不在线。');
  expect(executeGameControlCommand).not.toHaveBeenCalled();
  expect(screen.getByRole('dialog')).toBeInTheDocument();
  expect(screen.getByRole('spinbutton', { name: '基础等级' })).toHaveValue(
    '100',
  );
});

it('allows submission if the character has come online since the dialog opened', async () => {
  vi.mocked(getCharacterSnapshot)
    .mockResolvedValue({ data: { ...offline, online: true, base_level: 100 } })
    .mockResolvedValueOnce({ data: offline })
    .mockResolvedValueOnce({ data: { ...offline, online: true } });
  vi.mocked(executeGameControlCommand).mockResolvedValue({
    data: { result: { base_level: 100 } },
  });
  render(
    <App>
      <CharacterMaintenanceModal charId={42} characterName="上线测试角色" />
    </App>,
  );
  fireEvent.click(screen.getByRole('button', { name: /角色属性/ }));
  fireEvent.click(await screen.findByRole('tab', { name: '等级与点数' }));
  await waitFor(() =>
    expect(screen.getByRole('spinbutton', { name: '基础等级' })).toHaveValue(
      '99',
    ),
  );
  fireEvent.change(screen.getByRole('spinbutton', { name: '基础等级' }), {
    target: { value: '100' },
  });
  fireEvent.click(screen.getByRole('button', { name: '应用修改' }));
  await waitFor(() =>
    expect(executeGameControlCommand).toHaveBeenCalledWith(
      expect.objectContaining({
        type: 'character.progression.update',
        payload: { base_level: 100 },
      }),
    ),
  );
  await screen.findByText('操作成功，已读取最新状态');
  expect(screen.getByRole('dialog')).toBeInTheDocument();
  expect(screen.getByRole('spinbutton', { name: '基础等级' })).toHaveValue(
    '100',
  );
});

it('shows six functional tabs and keeps an untouched draft from triggering an offline error', async () => {
  vi.mocked(getCharacterSnapshot).mockResolvedValue({ data: offline });
  render(
    <App>
      <CharacterMaintenanceModal charId={42} characterName="测试角色" />
    </App>,
  );
  fireEvent.click(screen.getByRole('button', { name: /角色属性/ }));
  await screen.findByRole('tab', { name: '等级与点数' });
  expect(screen.getByRole('tab', { name: '职业' })).toHaveAttribute(
    'aria-selected',
    'true',
  );
  expect(screen.getAllByRole('tab')).toHaveLength(6);
  fireEvent.click(screen.getByRole('button', { name: '转换职业' }));
  await screen.findByText('没有需要应用的修改');
  expect(getCharacterSnapshot).toHaveBeenCalledTimes(1);
  expect(screen.queryByText('角色不在线。')).not.toBeInTheDocument();
});

it('submits zero points without unrelated fields and isolates the skills tab from level drafts', async () => {
  const snapshot = { ...offline, online: true };
  vi.mocked(getCharacterSnapshot).mockResolvedValue({ data: snapshot });
  vi.mocked(executeGameControlCommand).mockResolvedValue({
    data: { result: {} },
  });
  render(
    <App>
      <CharacterMaintenanceModal charId={42} characterName="测试角色" />
    </App>,
  );
  fireEvent.click(screen.getByRole('button', { name: /角色属性/ }));
  fireEvent.click(await screen.findByRole('tab', { name: '等级与点数' }));
  await screen.findByRole('spinbutton', { name: '素质点' });
  fireEvent.change(screen.getByRole('spinbutton', { name: '素质点' }), {
    target: { value: '0' },
  });
  fireEvent.click(screen.getByRole('button', { name: '应用修改' }));
  await waitFor(() =>
    expect(executeGameControlCommand).toHaveBeenCalledWith(
      expect.objectContaining({
        type: 'character.points.update',
        payload: { status_points: 0 },
      }),
    ),
  );
  await screen.findByText('操作成功，已读取最新状态');
  fireEvent.change(screen.getByRole('spinbutton', { name: '基础等级' }), {
    target: { value: '100' },
  });
  fireEvent.click(screen.getByRole('tab', { name: '技能' }));
  fireEvent.click(screen.getByRole('button', { name: '学满技能' }));
  fireEvent.click(await screen.findByRole('button', { name: '确认执行' }));
  await waitFor(() =>
    expect(executeGameControlCommand).toHaveBeenLastCalledWith(
      expect.objectContaining({
        type: 'character.skills.learn_all',
        payload: {},
      }),
    ),
  );
});
