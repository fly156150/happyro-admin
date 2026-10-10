import { App } from 'antd';
import { useRef, useState } from 'react';
import {
  type CharacterSnapshot,
  executeGameControlCommand,
  getCharacterSnapshot,
} from '@/services/operations/game-control';
import { createIdempotencyKey } from '@/utils/idempotency';
import {
  actionFields,
  changedCharacterValues,
  commandTypes,
  jobChangePayload,
  type MaintenanceAction,
} from './form-values';

const confirmations: Partial<Record<MaintenanceAction, string>> = {
  job: '确认转换职业？超过目标职业上限的等级会随转职下调。',
  statsReset: '确认重置全部基础属性并返还素质点？',
  traitsReset: '确认重置六项四转特性并返还特性点？',
  skills: '确认重置已学习的技能并返还技能点？',
  skillsLearnAll: '确认学满当前职业及继承职业的技能树？不消耗技能点。',
};

export function useMaintenance(charId: number) {
  const { message, modal } = App.useApp();
  const [open, setOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const [busy, setBusy] = useState(false);
  const [revision, setRevision] = useState(0);
  const [character, setCharacter] = useState<CharacterSnapshot>();
  const pending = useRef(false);
  const loadId = useRef(0);

  const close = () => {
    if (pending.current) return;
    loadId.current += 1;
    setOpen(false);
  };
  const show = async () => {
    const id = ++loadId.current;
    setOpen(true);
    setLoading(true);
    setCharacter(undefined);
    try {
      const { data } = await getCharacterSnapshot(charId);
      if (id !== loadId.current) return;
      setCharacter(data);
      setRevision((value) => value + 1);
    } catch {
      if (id === loadId.current) setOpen(false);
    } finally {
      if (id === loadId.current) setLoading(false);
    }
  };

  const apply = async (
    actions: MaintenanceAction[],
    values: Record<string, number> = {},
  ) => {
    if (pending.current || !character) return;
    const commands = actions
      .map((action) => ({
        action,
        payload: changedCharacterValues(action, values, character),
      }))
      .filter(
        ({ action, payload }) =>
          !actionFields[action].length || Object.keys(payload).length > 0,
      );
    if (!commands.length) {
      message.info('没有需要应用的修改');
      return;
    }
    pending.current = true;
    setBusy(true);
    try {
      let { data: live } = await getCharacterSnapshot(charId);
      if (live.online === false) {
        message.error('角色不在线。');
        return;
      }
      const job = commands.find((command) => command.action === 'job');
      if (job && !live.jobs?.some((entry) => entry.id === values.job_id)) {
        message.error('请选择当前可用的职业');
        return;
      }
      const lowering = commands.some(
        (command) =>
          command.action === 'progression' &&
          command.payload.base_level < Number(live.base_level),
      );
      const confirmation = lowering
        ? '确认降低基础等级？成长点数将被回收，点数不足时重置相应属性。'
        : confirmations[commands[0].action];
      if (
        confirmation &&
        !(await modal.confirm({
          title: '确认角色操作',
          content: confirmation,
          okText: '确认执行',
          cancelText: '取消',
        }))
      )
        return;
      for (const { action, payload } of commands) {
        const response = await executeGameControlCommand({
          idempotency_key: createIdempotencyKey(),
          type: commandTypes[action],
          target: { type: 'character', id: String(charId) },
          payload:
            action === 'job' ? jobChangePayload(values.job_id, live) : payload,
        });
        live = { ...live, ...response.data.result };
        setCharacter(live);
      }
      const { data } = await getCharacterSnapshot(charId);
      setCharacter(data);
      setRevision((value) => value + 1);
      message.success('操作成功，已读取最新状态');
    } catch {
      // API errors are presented by the shared request handler. Keep the draft for retry.
    } finally {
      pending.current = false;
      setBusy(false);
    }
  };

  return { open, loading, busy, revision, character, show, close, apply };
}
