import { useIntl } from '@umijs/max';
import { Button, Form, InputNumber, Select, Space, theme } from 'antd';
import type { ReactNode } from 'react';
import { groupedJobOptions } from '@/data/game/job-groups';
import { jobMappings } from '@/data/game/jobs';
import type { CharacterSnapshot } from '@/services/operations/game-control';
import {
  actionFields,
  characterFormValues,
  fieldMaximum,
  fieldMinimum,
  type MaintenanceAction,
} from './form-values';
import styles from './index.less';

export type MaintenanceTab =
  | 'job'
  | 'progression'
  | 'stats'
  | 'traits'
  | 'skills'
  | 'vitals';
type Props = {
  tab: MaintenanceTab;
  character: CharacterSnapshot;
  busy: boolean;
  onApply: (
    actions: MaintenanceAction[],
    values?: Record<string, number>,
  ) => Promise<void>;
};

export default function MaintenancePanel({
  tab,
  character,
  busy,
  onApply,
}: Props) {
  const intl = useIntl();
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });
  if (tab === 'skills')
    return <SkillPanel character={character} busy={busy} onApply={onApply} />;
  if (tab === 'vitals')
    return <VitalsPanel character={character} busy={busy} onApply={onApply} />;
  if (tab === 'traits' && !character.traits?.enabled)
    return (
      <div className={styles.panel}>
        当前角色没有可读取的四转特性；四转角色上线后可读取实时特性。
      </div>
    );
  const actions: MaintenanceAction[] =
    tab === 'progression' ? ['progression', 'points'] : [tab];
  const initialValues = Object.assign(
    {},
    ...actions.map((action) => characterFormValues(action, character)),
  );
  const jobs =
    character.online === false
      ? Object.keys(jobMappings).map((id) => ({ id: Number(id) }))
      : (character.jobs ?? []);
  return (
    <Form
      layout="vertical"
      initialValues={initialValues}
      disabled={busy}
      preserve={false}
      onFinish={(values) => onApply(actions, values)}
      className={styles.panel}
    >
      {tab === 'job' ? (
        <>
          <div className={styles.jobLayout}>
            <InfoCard>
              <div className={styles.caption}>当前职业</div>
              <div className={styles.jobValue}>
                {t(
                  jobMappings[Number(character.job_id)] ??
                    'players.job.unknown',
                  `职业 ${character.job_id}`,
                )}
              </div>
            </InfoCard>
            <InfoCard>
              <div className={styles.caption}>目标职业</div>
              <Form.Item
                name="job_id"
                className={styles.jobSelection}
                rules={[{ required: true, message: '请选择目标职业' }]}
              >
                <Select
                  aria-label="目标职业"
                  showSearch={{ optionFilterProp: 'label' }}
                  options={groupedJobOptions(jobs, t)}
                  placeholder="按职业名称搜索"
                  listHeight={360}
                />
              </Form.Item>
            </InfoCard>
          </div>
          <p className={styles.hint}>
            超过目标职业上限的等级会随转职下调，请确认目标职业后再转换。
          </p>
        </>
      ) : (
        <div className={styles.grid}>
          {actions.flatMap((action) =>
            actionFields[action].map((field) => (
              <Form.Item
                key={field}
                name={field}
                label={t(`players.character.field.${field}`, field)}
                rules={[
                  { required: true, message: '请输入数值' },
                  {
                    type: 'integer',
                    min: fieldMinimum(action),
                    max: fieldMaximum(action, field, character),
                    message: '请输入允许范围内的整数',
                  },
                ]}
              >
                <InputNumber
                  aria-label={t(`players.character.field.${field}`, field)}
                  min={fieldMinimum(action)}
                  max={fieldMaximum(action, field, character)}
                  precision={0}
                  style={{ width: '100%' }}
                />
              </Form.Item>
            )),
          )}
        </div>
      )}
      {tab === 'traits' && (
        <p>
          剩余特性点：{character.traits?.points}
          。直接设置特性不消耗点数，也不受剩余点数限制。
        </p>
      )}
      <Space wrap>
        <Button type="primary" htmlType="submit" loading={busy}>
          {tab === 'job' ? '转换职业' : '应用修改'}
        </Button>
        {tab === 'stats' && (
          <Button danger onClick={() => void onApply(['statsReset'])}>
            重置基础属性
          </Button>
        )}
        {tab === 'traits' && (
          <Button danger onClick={() => void onApply(['traitsReset'])}>
            重置四转特性
          </Button>
        )}
      </Space>
    </Form>
  );
}

function SkillPanel({ character, busy, onApply }: Omit<Props, 'tab'>) {
  return (
    <div className={styles.panel}>
      <InfoCard>
        <div className={styles.caption}>剩余技能点</div>
        <div className={styles.value}>
          {String(character.skill_points ?? '—')}
        </div>
      </InfoCard>
      <div className={styles.grid}>
        <InfoCard>
          <div className={styles.cardTitle}>学习职业技能</div>
          <p className={styles.hint}>
            学满当前职业及继承职业的技能树，不消耗剩余技能点。
          </p>
          <Button
            type="primary"
            disabled={busy}
            onClick={() => void onApply(['skillsLearnAll'])}
          >
            学满技能
          </Button>
        </InfoCard>
        <InfoCard>
          <div className={styles.cardTitle}>重新分配技能</div>
          <p className={styles.hint}>
            重置已学习的技能，返还已使用的技能点。执行前需要确认。
          </p>
          <Button
            danger
            disabled={busy}
            onClick={() => void onApply(['skills'])}
          >
            重置技能
          </Button>
        </InfoCard>
      </div>
    </div>
  );
}

function VitalsPanel({ character, busy, onApply }: Omit<Props, 'tab'>) {
  return (
    <div className={styles.panel}>
      <div className={styles.vitalsGrid}>
        {[
          { label: '生命 HP', current: character.hp, max: character.max_hp },
          { label: 'SP', current: character.sp, max: character.max_sp },
          ...(character.max_ap === undefined
            ? []
            : [
                {
                  label: 'AP',
                  current: character.ap,
                  max: character.max_ap,
                },
              ]),
        ].map(({ label, current, max }) => (
          <InfoCard key={label}>
            <div className={styles.caption}>{label}</div>
            <div className={styles.value}>
              {String(current ?? '—')}
              <span className={styles.maximum}> / {String(max ?? '—')}</span>
            </div>
          </InfoCard>
        ))}
      </div>
      <InfoCard>
        <div className={styles.cardTitle}>恢复角色状态</div>
        <p className={styles.hint}>
          将生命、SP 和 AP 恢复至上限。角色在线时可执行。
        </p>
        <Button
          type="primary"
          disabled={busy}
          onClick={() => void onApply(['vitals'])}
        >
          恢复状态
        </Button>
      </InfoCard>
    </div>
  );
}

function InfoCard({ children }: { children: ReactNode }) {
  const { token } = theme.useToken();
  return (
    <div
      className={styles.card}
      style={{
        borderColor: token.colorBorderSecondary,
        background: token.colorFillAlter,
        color: token.colorText,
      }}
    >
      {children}
    </div>
  );
}
