import { EditOutlined } from '@ant-design/icons';
import { ProFormDigit, ProFormSelect } from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { App, Button, Form, Modal, Space } from 'antd';
import { useRef, useState } from 'react';
import {
  executeGameControlCommand,
  getCharacterSnapshot,
  type CharacterSnapshot,
} from '@/services/operations/game-control';
import { createIdempotencyKey } from '@/utils/idempotency';
import { jobMappings } from '@/data/game/jobs';
import {
  actionFields,
  characterFormValues,
  fieldMaximum,
  jobChangePayload,
  mergeCharacterResult,
  type MaintenanceAction,
} from './form-values';

type Props = { charId: number; characterName: string };

const commandTypes: Record<MaintenanceAction, string> = {
  job: 'character.progression.update',
  skillPoints: 'character.skill_points.update',
  progression: 'character.progression.update',
  stats: 'character.stats.update',
  statsReset: 'character.stats.reset',
  traits: 'character.traits.update',
  traitsReset: 'character.traits.reset',
  skillsLearnAll: 'character.skills.learn_all',
  skills: 'character.skills.reset',
  vitals: 'character.vitals.restore',
};

export default function CharacterMaintenanceModal({
  charId,
  characterName,
}: Props) {
  const intl = useIntl();
  const { message } = App.useApp();
  const [open, setOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const submittingRef = useRef(false);
  const [action, setAction] = useState<MaintenanceAction>('progression');
  const [character, setCharacter] = useState<CharacterSnapshot>();
  const [form] = Form.useForm<Record<string, number>>();
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });
  const fields = actionFields[action];
  const close = () => {
    if (submitting) return;
    setOpen(false);
    form.resetFields();
  };
  const openMaintenance = async () => {
    setOpen(true);
    setLoading(true);
    try {
      const response = await getCharacterSnapshot(charId);
      setCharacter(response.data);
      const nextAction =
        (action === 'traits' || action === 'traitsReset') &&
        !response.data.traits?.enabled
          ? 'progression'
          : action;
      setAction(nextAction);
      form.setFieldsValue(characterFormValues(nextAction, response.data));
    } catch (_error) {
      setOpen(false);
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <Button
        type="link"
        size="small"
        icon={<EditOutlined />}
        onClick={() => void openMaintenance()}
      >
        {t('players.character.maintain', '角色属性')}
      </Button>
      <Modal
        title={`${t('players.character.maintain', '角色属性')} · ${characterName}`}
        open={open}
        onCancel={close}
        okText={t('common.confirm', '确认')}
        cancelText={t('common.cancel', '取消')}
        onOk={() => form.submit()}
        okButtonProps={{ disabled: loading }}
        confirmLoading={submitting}
        cancelButtonProps={{ disabled: submitting }}
        closable={!submitting}
        maskClosable={!submitting}
        destroyOnHidden
      >
        <Form
          form={form}
          layout="vertical"
          disabled={loading || submitting}
          onFinish={async (values) => {
            if (submittingRef.current) return;
            const original = character
              ? characterFormValues(action, character)
              : {};
            const payload = Object.fromEntries(
              Object.entries(values).filter(
                ([key, value]) => value !== original[key],
              ),
            );
            if (fields.length && !Object.keys(payload).length) {
              message.info('没有需要应用的修改');
              return;
            }
            setSubmitting(true);
            submittingRef.current = true;
            try {
              const response = await executeGameControlCommand({
                idempotency_key: createIdempotencyKey(),
                type: commandTypes[action],
                target: { type: 'character', id: String(charId) },
                // Ant Design omits the values object when an action has no fields.
                // Game Control still requires an explicit object payload.
                payload:
                  action === 'job' && character
                    ? jobChangePayload(values.job_id, character)
                    : fields.length === 0
                      ? {}
                      : payload,
              });
              setCharacter((current) =>
                mergeCharacterResult(current, response.data.result),
              );
              message.success(
                t('players.character.maintainSuccess', '操作已提交'),
              );
              setOpen(false);
              form.resetFields();
            } finally {
              setSubmitting(false);
              submittingRef.current = false;
            }
          }}
        >
          <ProFormSelect
            label={t('players.character.maintainAction', '操作')}
            valueEnum={{
              job: '转换职业',
              progression: '等级',
              skillPoints: '技能点',
              stats: t('players.character.stats', '属性'),
              statsReset: t('players.character.statsReset', '重置属性'),
              ...(character?.traits?.enabled
                ? { traits: '特性属性', traitsReset: '重置特性属性' }
                : {}),
              skillsLearnAll: '学满职业技能',
              skills: t('players.character.skillsReset', '重置技能点'),
              vitals: t('players.character.vitalsRestore', '恢复状态'),
            }}
            fieldProps={{
              value: action,
              disabled: loading || submitting,
              onChange: (value) => {
                const nextAction = value as MaintenanceAction;
                setAction(nextAction);
                form.resetFields();
                if (character) {
                  form.setFieldsValue(
                    characterFormValues(nextAction, character),
                  );
                }
              },
            }}
          />
          {fields.map((field) =>
            field === 'job_id' ? (
              <ProFormSelect
                key={field}
                name={field}
                label="职业"
                showSearch
                options={(character?.jobs ?? []).map(({ id }) => ({
                  value: id,
                  label: t(jobMappings[id], `职业 ${id}`),
                }))}
                fieldProps={{ optionFilterProp: 'label' }}
                rules={[{ required: true }]}
              />
            ) : (
              <ProFormDigit
                key={field}
                name={field}
                label={t(`players.character.field.${field}`, field)}
                min={field === 'skill_points' || action === 'traits' ? 0 : 1}
                max={fieldMaximum(action, field, character)}
                width="md"
                rules={[
                  { required: true },
                  ...(fieldMaximum(action, field, character) !== undefined
                    ? [
                        {
                          type: 'number' as const,
                          max: fieldMaximum(action, field, character),
                          message: `不能超过 ${fieldMaximum(action, field, character)}`,
                        },
                      ]
                    : []),
                ]}
              />
            ),
          )}
          {action === 'statsReset' && (
            <Space>重置该角色的基础属性，并返还属性点。</Space>
          )}
          {action === 'job' && (
            <Space>超过目标职业上限的 Base / Job 等级会随转职下调。</Space>
          )}
          {action === 'traits' && (
            <Space>
              直接设置特性值，不受当前特性点限制。当前剩余：
              {character?.traits?.points}，设置不消耗剩余点数。
            </Space>
          )}
          {action === 'traitsReset' && (
            <Space>重置全部特性属性，并返还特性点。</Space>
          )}
          {action === 'skillsLearnAll' && (
            <Space>将当前职业及前置职业的可学习技能升满，不消耗技能点。</Space>
          )}
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
