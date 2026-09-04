import { PageContainer, ProTable } from '@ant-design/pro-components';
import { request, useIntl } from '@umijs/max';
import { Flex, Tabs, Tag, Typography } from 'antd';

type Revision = {
  id: number;
  revision: number;
  reason: string;
  status: string;
  changes: Record<string, number>;
  requester?: { username?: string; name?: string };
  created_at: string;
};

type GameDataChange = {
  id: number;
  metadata: {
    before: { clientVersion: string; serverVersion: string };
    after: { clientVersion: string; serverVersion: string };
  };
  user?: { username?: string; name?: string };
  created_at: string;
};

function formatRate(value: number): string {
  return `${(value / 100).toFixed(2)}x`;
}

export default function GameRuleHistory() {
  const intl = useIntl();
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });
  return (
    <PageContainer
      title={t('settings.configurationHistory.title', '配置修改记录')}
    >
      <Tabs
        items={[
          {
            key: 'rules',
            label: t('settings.configurationHistory.rules', '游戏倍率'),
            children: (
              <ProTable<Revision>
                rowKey="id"
                search={false}
                scroll={{ x: 1040 }}
                request={async ({ current }) => {
                  const response = await request<{
                    data: Revision[];
                    meta: { total: number };
                  }>(`/api/settings/game-rules/history?page=${current ?? 1}`);
                  return {
                    data: response.data,
                    total: response.meta.total,
                    success: true,
                  };
                }}
                columns={[
                  {
                    title: t('settings.gameRuleHistory.revision', '版本'),
                    dataIndex: 'revision',
                  },
                  {
                    title: t('settings.gameRuleHistory.changes', '修改内容'),
                    dataIndex: 'changes',
                    width: 520,
                    render: (_, record) => (
                      <Flex gap={8} wrap>
                        {Object.entries(record.changes).map(([key, value]) => (
                          <Tag
                            key={key}
                            style={{ marginInlineEnd: 0, padding: '3px 8px' }}
                          >
                            <Typography.Text type="secondary">
                              {t(`settings.gameRules.key.${key}`, key)}
                            </Typography.Text>{' '}
                            <Typography.Text strong>
                              {formatRate(value)}
                            </Typography.Text>
                          </Tag>
                        ))}
                      </Flex>
                    ),
                  },
                  {
                    title: t('settings.gameRules.reason', '修改原因'),
                    dataIndex: 'reason',
                  },
                  {
                    title: t('settings.gameRuleHistory.operator', '操作人'),
                    render: (_, record) =>
                      record.requester?.username ??
                      record.requester?.name ??
                      '-',
                  },
                  {
                    title: t('common.status', '状态'),
                    dataIndex: 'status',
                    render: (_, record) => (
                      <Tag
                        color={
                          record.status === 'applied'
                            ? 'success'
                            : record.status === 'failed'
                              ? 'error'
                              : 'processing'
                        }
                      >
                        {t(
                          `settings.configurationHistory.status.${record.status}`,
                          record.status,
                        )}
                      </Tag>
                    ),
                  },
                  {
                    title: t('common.createdAt', '创建时间'),
                    dataIndex: 'created_at',
                    valueType: 'dateTime',
                  },
                ]}
              />
            ),
          },
          {
            key: 'game-data',
            label: t('settings.configurationHistory.gameData', '游戏资料版本'),
            children: (
              <ProTable<GameDataChange>
                rowKey="id"
                search={false}
                request={async ({ current }) => {
                  const response = await request<{
                    data: GameDataChange[];
                    meta: { total: number };
                  }>(`/api/settings/game-data/history?page=${current ?? 1}`);
                  return {
                    data: response.data,
                    total: response.meta.total,
                    success: true,
                  };
                }}
                columns={[
                  {
                    title: t('settings.configurationHistory.before', '修改前'),
                    render: (_, record) =>
                      `${record.metadata.before.clientVersion} / ${record.metadata.before.serverVersion}`,
                  },
                  {
                    title: t('settings.configurationHistory.after', '修改后'),
                    render: (_, record) =>
                      `${record.metadata.after.clientVersion} / ${record.metadata.after.serverVersion}`,
                  },
                  {
                    title: t('settings.gameRuleHistory.operator', '操作人'),
                    render: (_, record) =>
                      record.user?.username ?? record.user?.name ?? '-',
                  },
                  {
                    title: t('common.createdAt', '创建时间'),
                    dataIndex: 'created_at',
                    valueType: 'dateTime',
                  },
                ]}
              />
            ),
          },
        ]}
      />
    </PageContainer>
  );
}
