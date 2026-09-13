import { PageContainer, ProTable } from '@ant-design/pro-components';
import { request, useIntl } from '@umijs/max';
import { Flex, Tag, Typography } from 'antd';

type Revision = {
  id: number;
  revision: number;
  remark: string | null;
  status: string;
  changes: Record<string, number>;
  requester?: { username?: string; name?: string };
  created_at: string;
};

function formatChange(key: string, value: number): string {
  return key.includes('_rate') ? (value / 100).toFixed(2) : String(value);
}

export default function GameSettingHistory() {
  const intl = useIntl();
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });
  return (
    <PageContainer
      title={t('settings.configurationHistory.title', '配置修改记录')}
    >
      <ProTable<Revision>
        rowKey="id"
        search={false}
        scroll={{ x: 1040 }}
        request={async ({ current }) => {
          const response = await request<{
            data: Revision[];
            meta: { total: number };
          }>(`/api/settings/game-settings/history?page=${current ?? 1}`);
          return {
            data: response.data,
            total: response.meta.total,
            success: true,
          };
        }}
        columns={[
          {
            title: t('settings.gameSettingHistory.revision', '版本'),
            dataIndex: 'revision',
          },
          {
            title: t('settings.gameSettingHistory.changes', '修改内容'),
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
                      {t(`settings.gameSettings.key.${key}`, key)}
                    </Typography.Text>{' '}
                    <Typography.Text strong>
                      {formatChange(key, value)}
                    </Typography.Text>
                  </Tag>
                ))}
              </Flex>
            ),
          },
          {
            title: t('settings.gameSettings.remark', '修改备注'),
            dataIndex: 'remark',
            render: (_, record) => record.remark || '-',
          },
          {
            title: t('settings.gameSettingHistory.operator', '操作人'),
            render: (_, record) =>
              record.requester?.username ?? record.requester?.name ?? '-',
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
    </PageContainer>
  );
}
