import { useIntl } from '@umijs/max';
import {
  Alert,
  App,
  Button,
  Form,
  InputNumber,
  Modal,
  Select,
  Space,
} from 'antd';
import { useEffect, useRef, useState } from 'react';
import { mapMappings } from '@/data/game/maps';
import { executeGameControlCommand } from '@/services/operations/game-control';
import { listCharacters } from '@/services/players/queries';
import { createIdempotencyKey } from '@/utils/idempotency';

type Props = {
  map: string;
  mapName?: string | null;
  name: string;
  x?: number;
  y?: number;
  npcClass?: number;
};

export default function CharacterNavigationModal({
  map,
  mapName,
  name,
  x = 0,
  y = 0,
  npcClass,
}: Props) {
  const { message } = App.useApp();
  const intl = useIntl();
  const destinationName = mapMappings[map]
    ? intl.formatMessage({
        id: mapMappings[map],
        defaultMessage: mapName || map,
      })
    : mapName || map;
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
  }>();
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
        okText="确认传送"
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
          initialValues={{ x, y }}
          onFinish={async (values) => {
            if (pending.current) return;
            pending.current = true;
            setBusy(true);
            try {
              await executeGameControlCommand({
                idempotency_key: createIdempotencyKey(),
                type: 'character.navigation.teleport',
                target: { type: 'character', id: String(values.target) },
                payload: {
                  map,
                  x: npcClass ? x : values.x,
                  y: npcClass ? y : values.y,
                  ...(npcClass ? { npc_class: npcClass } : {}),
                },
              });
              message.success('角色已传送');
              pending.current = false;
              close();
            } finally {
              pending.current = false;
              setBusy(false);
            }
          }}
        >
          <Alert
            style={{ marginTop: 24, marginBottom: 24 }}
            type="info"
            showIcon
            message={
              npcClass
                ? `目标位置：${destinationName} (${x}, ${y}) 的 NPC 附近。`
                : `目标位置：${destinationName}，坐标都为 0 时随机选择落点。`
            }
          />
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
          {!npcClass && (
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
