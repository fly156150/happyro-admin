import { EditOutlined } from '@ant-design/icons';
import { ProFormDigit, ProFormSelect } from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { App, Button, Form, Modal, Space } from 'antd';
import { useState } from 'react';
import { executeGameControlCommand } from '@/services/operations/game-control';
import { createIdempotencyKey } from '@/utils/idempotency';

type Props = { charId: number; characterName: string };
type Action = 'progression' | 'stats' | 'statsReset' | 'skills' | 'vitals';

const commandTypes: Record<Action, string> = {
  progression: 'character.progression.update',
  stats: 'character.stats.update',
  statsReset: 'character.stats.reset',
  skills: 'character.skills.reset',
  vitals: 'character.vitals.restore',
};

const actionFields: Record<Action, string[]> = {
  progression: ['base_level', 'job_level', 'job_id'],
  stats: ['str', 'agi', 'vit', 'int', 'dex', 'luk'],
  statsReset: [],
  skills: [],
  vitals: [],
};

export default function CharacterMaintenanceModal({
  charId,
  characterName,
}: Props) {
  const intl = useIntl();
  const { message } = App.useApp();
  const [open, setOpen] = useState(false);
  const [action, setAction] = useState<Action>('progression');
  const [form] = Form.useForm<Record<string, number>>();
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });
  const fields = actionFields[action];
  const close = () => {
    setOpen(false);
    form.resetFields();
  };

  return (
    <>
      <Button
        type="link"
        size="small"
        icon={<EditOutlined />}
        onClick={() => setOpen(true)}
      >
        {t('players.character.maintain', '维护')}
      </Button>
      <Modal
        title={`${t('players.character.maintain', '维护')} · ${characterName}`}
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
          onFinish={async (values) => {
            await executeGameControlCommand({
              idempotency_key: createIdempotencyKey(),
              type: commandTypes[action],
              target: { type: 'character', id: String(charId) },
              // Ant Design omits the values object when an action has no fields.
              // Game Control still requires an explicit object payload.
              payload: fields.length === 0 ? {} : values,
            });
            message.success(
              t('players.character.maintainSuccess', '操作已提交'),
            );
            close();
          }}
        >
          <ProFormSelect
            label={t('players.character.maintainAction', '操作')}
            valueEnum={{
              progression: t('players.character.progression', '等级与职业'),
              stats: t('players.character.stats', '属性'),
              statsReset: t('players.character.statsReset', '重置属性'),
              skills: t('players.character.skillsReset', '重置技能点'),
              vitals: t('players.character.vitalsRestore', '恢复状态'),
            }}
            fieldProps={{
              value: action,
              onChange: (value) => {
                setAction(value as Action);
                form.resetFields();
              },
            }}
          />
          {fields.map((field) => (
            <ProFormDigit
              key={field}
              name={field}
              label={t(`players.character.field.${field}`, field)}
              min={1}
              width="md"
              rules={[{ required: true }]}
            />
          ))}
          {action === 'skills' && (
            <Space>
              {t(
                'players.character.skillsResetConfirm',
                '将重置该角色的技能点。',
              )}
            </Space>
          )}
          {action === 'vitals' && (
            <Space>
              {t(
                'players.character.vitalsRestoreConfirm',
                '将恢复该角色的生命、SP 和 AP。',
              )}
            </Space>
          )}
        </Form>
      </Modal>
    </>
  );
}
