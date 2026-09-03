import { DeploymentUnitOutlined } from '@ant-design/icons';
import { ProFormDigit } from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { App, AutoComplete, Button, Form, Modal, Space } from 'antd';
import { useState } from 'react';
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
        destroyOnHidden
      >
        <Form
          form={form}
          layout="vertical"
          initialValues={{ count: 1, radius: 3, duration: 300 }}
          onFinish={async (values) => {
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
            close();
          }}
        >
          <Form.Item
            name="target"
            label={t('gameData.monster.spawnTarget', '在线目标角色')}
            rules={[{ required: true }]}
          >
            <AutoComplete
              placeholder={t(
                'gameData.monster.spawnTargetPlaceholder',
                '输入角色名或账号搜索',
              )}
              onChange={async (value) => {
                if (!value.trim()) {
                  setTargets([]);
                  return;
                }
                const result = await listCharacters({
                  current: 1,
                  pageSize: 20,
                  username: value,
                });
                setTargets(
                  result.data.filter(
                    (character) => Number(character.online) === 1,
                  ) as Character[],
                );
              }}
              options={targets.map((character) => ({
                value: character.char_id,
                label: `${character.name} · ${character.username ?? ''} (#${character.char_id})`,
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
