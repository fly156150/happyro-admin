import {
  App,
  Button,
  Form,
  InputNumber,
  Modal,
  Select,
  Space,
  Alert,
} from 'antd';
import { useEffect, useRef, useState } from 'react';
import { listCharacters } from '@/services/players/queries';
import { executeGameControlCommand } from '@/services/operations/game-control';
import { createIdempotencyKey } from '@/utils/idempotency';

type Props = {
  map: string;
  name: string;
  x?: number;
  y?: number;
  npcClass?: number;
};

export default function CharacterNavigationModal({
  map,
  name,
  x = 0,
  y = 0,
  npcClass,
}: Props) {
  const { message } = App.useApp();
  const [open, setOpen] = useState(false);
  const [busy, setBusy] = useState(false);
  const pending = useRef(false);
  const searchId = useRef(0);
  const [options, setOptions] = useState<{ value: number; label: string }[]>(
    [],
  );
  const [form] = Form.useForm<{
    target: number;
    x: number;
    y: number;
    action: 'teleport' | 'walk' | 'preview' | 'stop';
  }>();
  const action = Form.useWatch('action', form) ?? 'teleport';
  useEffect(
    () => () => {
      searchId.current += 1;
    },
    [],
  );
  const close = () => {
    if (pending.current) return;
    searchId.current += 1;
    setOpen(false);
    setOptions([]);
    form.resetFields();
  };
  return (
    <>
      <Button type="link" size="small" onClick={() => setOpen(true)}>
        位置操作
      </Button>
      <Modal
        title={`角色位置操作 · ${name}`}
        open={open}
        onCancel={close}
        onOk={() => form.submit()}
        okText="确认执行"
        cancelText="取消"
        confirmLoading={busy}
        closable={!busy}
        maskClosable={!busy}
        cancelButtonProps={{ disabled: busy }}
        destroyOnHidden
      >
        <Form
          form={form}
          layout="vertical"
          disabled={busy}
          initialValues={{ x, y, action: 'teleport' }}
          onFinish={async (values) => {
            if (pending.current) return;
            pending.current = true;
            setBusy(true);
            try {
              await executeGameControlCommand({
                idempotency_key: createIdempotencyKey(),
                type:
                  values.action === 'teleport'
                    ? 'character.navigation.teleport'
                    : 'character.navigation.route',
                target: { type: 'character', id: String(values.target) },
                payload:
                  values.action === 'stop'
                    ? { action: 'stop' }
                    : {
                        map,
                        x: npcClass ? x : values.x,
                        y: npcClass ? y : values.y,
                        ...(npcClass ? { npc_class: npcClass } : {}),
                        ...(values.action !== 'teleport'
                          ? { action: values.action }
                          : {}),
                      },
              });
              message.success(
                values.action === 'teleport'
                  ? '角色已传送'
                  : '寻路指令已发送，请在游戏内查看执行状态',
              );
              pending.current = false;
              close();
            } finally {
              pending.current = false;
              setBusy(false);
            }
          }}
        >
          <Alert
            type="info"
            showIcon
            message={
              action !== 'teleport'
                ? '在目标角色的游戏界面预览、开始或停止寻路；只支持角色当前地图，实际移动遵守游戏规则。'
                : npcClass
                  ? `传送至 ${map} (${x}, ${y}) 的 NPC 附近；隐藏或未开放的 NPC 无法传送。`
                  : `目标地图 ${map}；坐标都为 0 时随机选择落点。遵守游戏内传送权限、地图限制和冷却。`
            }
          />
          <Form.Item name="action" label="操作" rules={[{ required: true }]}>
            <Select
              options={[
                { value: 'teleport', label: '传送' },
                { value: 'preview', label: '在游戏中预览路径' },
                { value: 'walk', label: '同地图自动寻路' },
                { value: 'stop', label: '停止自动寻路' },
              ]}
            />
          </Form.Item>
          <Form.Item
            name="target"
            label="在线目标角色"
            rules={[{ required: true }]}
          >
            <Select
              showSearch
              filterOption={false}
              options={options}
              placeholder="输入角色名或账号搜索"
              onSearch={async (value) => {
                const id = ++searchId.current;
                if (!value.trim()) {
                  setOptions([]);
                  return;
                }
                try {
                  const response = await listCharacters({
                    current: 1,
                    pageSize: 20,
                    username: value,
                  });
                  if (id !== searchId.current) return;
                  setOptions(
                    response.data
                      .filter((character) => Number(character.online) === 1)
                      .map((character) => ({
                        value: Number(character.char_id),
                        label: `${character.name} · ${character.username ?? ''}`,
                      })),
                  );
                } catch {
                  if (id === searchId.current) setOptions([]);
                }
              }}
            />
          </Form.Item>
          {!npcClass && action !== 'stop' && (
            <Space>
              <Form.Item name="x" label="X" rules={[{ required: true }]}>
                <InputNumber min={0} max={32767} precision={0} />
              </Form.Item>
              <Form.Item name="y" label="Y" rules={[{ required: true }]}>
                <InputNumber min={0} max={32767} precision={0} />
              </Form.Item>
            </Space>
          )}
        </Form>
      </Modal>
    </>
  );
}
