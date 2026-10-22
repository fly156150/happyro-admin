import { type ActionType, ProTable } from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { App, Button, Tag } from 'antd';
import { useRef, useState } from 'react';
import {
  type GameSettingDefinition,
  listSettingRevisions,
  resumeSettingRevision,
  type SettingRevision,
} from '@/api/settings/game-settings';

export default function RevisionHistory({
  revision,
  onApplied,
}: {
  revision: number;
  onApplied?: () => Promise<void>;
}) {
  const intl = useIntl();
  const { message } = App.useApp();
  const actionRef = useRef<ActionType>(null);
  const [definitions, setDefinitions] = useState<
    Record<string, GameSettingDefinition>
  >({});
  const [pending, setPending] = useState<string>();
  const t = (id: string, defaultMessage: string) =>
    intl.formatMessage({ id, defaultMessage });
  const labels = {
    prepared: '等待发布',
    published: '等待确认生效',
    applied: '已生效',
    failed: '失败',
    uncertain: '结果待确认',
  };
  const resume = async (id: string) => {
    setPending(id);
    try {
      const result = await resumeSettingRevision(id);
      if (result.data.status === 'applied') {
        message.success(t('settings.revision.applied', '设置已确认生效'));
        await onApplied?.();
      }
    } catch {
      // Shared request handling displays errors without claiming completion.
    } finally {
      setPending(undefined);
      actionRef.current?.reload();
    }
  };
  return (
    <ProTable<SettingRevision>
      headerTitle={t('settings.revision.title', '设置修改记录')}
      actionRef={actionRef}
      rowKey="id"
      search={false}
      params={{ revision }}
      pagination={{ defaultPageSize: 10 }}
      request={async (params) => {
        const result = await listSettingRevisions(params);
        setDefinitions(result.definitions);
        return { data: result.data, total: result.meta.total, success: true };
      }}
      columns={[
        {
          title: t('settings.revision.time', '提交时间'),
          dataIndex: 'created_at',
          valueType: 'dateTime',
        },
        {
          title: t('settings.revision.actor', '操作账号'),
          render: (_, row) =>
            row.requested_by !== null
              ? `${t('settings.revision.admin', '后台账号')} #${row.requested_by}`
              : `${t('settings.revision.player', '游戏账号')} #${row.requested_game_account_id}`,
        },
        {
          title: t('settings.revision.changes', '修改内容'),
          render: (_, row) =>
            Object.entries(row.changes).map(([key, value]) => (
              <div key={key}>
                {t(`settings.gameSettings.key.${key}`, key)}：
                {definitions?.[key]?.unit === 'percent'
                  ? `${value / 100} 倍`
                  : value}
              </div>
            )),
        },
        {
          title: t('settings.revision.status', '状态'),
          render: (_, row) => (
            <Tag
              color={
                row.status === 'applied'
                  ? 'success'
                  : row.status === 'failed'
                    ? 'error'
                    : 'processing'
              }
            >
              {t(`settings.revision.status.${row.status}`, labels[row.status])}
            </Tag>
          ),
        },
        {
          title: t('common.actions', '操作'),
          valueType: 'option',
          render: (_, row) =>
            ['prepared', 'published', 'uncertain'].includes(row.status) ? (
              <Button
                key="resume"
                type="link"
                disabled={pending !== undefined}
                loading={pending === row.id}
                onClick={() => void resume(row.id)}
              >
                {t('settings.revision.resume', '继续确认')}
              </Button>
            ) : null,
        },
      ]}
    />
  );
}
