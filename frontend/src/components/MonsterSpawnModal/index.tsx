import { DeploymentUnitOutlined } from '@ant-design/icons';
import { ProFormDigit } from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { App, Select, Button, Form, Modal, Space } from 'antd';
import { useEffect, useRef, useState } from 'react';
import { executeGameControlCommand } from '@/services/operations/game-control';
import { createIdempotencyKey } from '@/utils/idempotency';
import { listCharacters } from '@/services/players/queries';

type Props = { monsterId: number; monsterName: string };
type Character = {
  char_id: number;
  name: string;
  username?: string;
  online?: number;
};

export default function MonsterSpawnModal({ monsterId, monsterName }: Props) {
  const intl = useIntl();
  const { message } = App.useApp();
  const [open, setOpen] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const submittingRef = useRef(false);
  const searchId = useRef(0);
  useEffect(
    () => () => {
      searchId.current += 1;
    },
    [],
  );
  const [targets, setTargets] = useState<Character[]>([]);
  const [form] = Form.useForm<{
    target: number;
    count: number;
    radius: number;
    duration: number;
  }>();
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });
  const close = () => {
    if (submittingRef.current) return;
    searchId.current += 1;
    setOpen(false);
    form.resetFields();
    setTargets([]);
  };

  return (
    <>
      <Button
        type="link"
        size="small"
        icon={<DeploymentUnitOutlined />}
        onClick={() => setOpen(true)}
      >
        {t('gameData.monster.spawn', '召唤')}
      </Button>
      <Modal
        title={`${t('gameData.monster.spawn', '召唤')} · ${monsterName}`}
        open={open}
        onCancel={close}
        okText={t('common.confirm', '确认')}
        cancelText={t('common.cancel', '取消')}
        onOk={() => form.submit()}
        confirmLoading={submitting}
        closable={!submitting}
        maskClosable={!submitting}
        cancelButtonProps={{ disabled: submitting }}
        destroyOnHidden
      >
        <Form
          form={form}
          layout="vertical"
          disabled={submitting}
          initialValues={{ count: 1, radius: 3, duration: 300 }}
          onFinish={async (values) => {
            if (submittingRef.current) return;
            submittingRef.current = true;
            setSubmitting(true);
            try {
              await executeGameControlCommand({
                idempotency_key: createIdempotencyKey(),
                type: 'monster.spawn',
                target: { type: 'character', id: String(values.target) },
                payload: {
                  monster_id: monsterId,
                  count: values.count,
                  radius: values.radius,
                  duration_seconds: values.duration,
                },
              });
              message.success(
                t('gameData.monster.spawnSuccess', '召唤命令已提交'),
              );
              setOpen(false);
              form.resetFields();
              setTargets([]);
            } finally {
              submittingRef.current = false;
              setSubmitting(false);
            }
          }}
        >
          <Form.Item
            name="target"
            label={t('gameData.monster.spawnTarget', '在线目标角色')}
            rules={[{ required: true }]}
          >
            <Select
              showSearch
              filterOption={false}
              placeholder={t(
                'gameData.monster.spawnTargetPlaceholder',
                '输入角色名或账号搜索',
              )}
              onSearch={async (value) => {
                const currentSearch = ++searchId.current;
                if (!value.trim()) {
                  setTargets([]);
                  return;
                }
                try {
                  const result = await listCharacters({
                    current: 1,
                    pageSize: 20,
                    username: value,
                  });
                  if (currentSearch !== searchId.current) return;
                  setTargets(
                    result.data.filter(
                      (character) => Number(character.online) === 1,
                    ) as Character[],
                  );
                } catch {
                  if (currentSearch === searchId.current) setTargets([]);
                }
              }}
              options={targets.map((character) => ({
                value: character.char_id,
                label: `${character.name} · ${character.username ?? ''}`,
              }))}
            />
          </Form.Item>
          <Space size="middle" wrap>
            <ProFormDigit
              name="count"
              label={t('gameData.monster.spawnCount', '数量')}
              min={1}
              max={10}
              rules={[{ required: true }]}
            />
            <ProFormDigit
              name="radius"
              label={t('gameData.monster.spawnRadius', '范围')}
              min={1}
              max={10}
              rules={[{ required: true }]}
            />
            <ProFormDigit
              name="duration"
              label={t('gameData.monster.spawnDuration', '持续时间（秒）')}
              min={1}
              max={3600}
              rules={[{ required: true }]}
            />
          </Space>
        </Form>
      </Modal>
    </>
  );
}
