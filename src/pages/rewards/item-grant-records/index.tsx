import { PageContainer, ProTable } from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { Select, Tag } from 'antd';
import { useState } from 'react';
import { listItemGrants } from '@/api/rewards/item-grants';
import { formatDateTime } from '@/utils/dateTime';

export default function ItemGrantRecords() {
  const intl = useIntl();
  const [kind, setKind] = useState('');
  const kinds = { mail: '邮件物品', inventory: '背包物品', zeny: 'Zeny' };
  const statuses: Record<string, string> = {
    sent: '已发送',
    succeeded: '已完成',
    pending: '等待处理',
    running: '执行中',
    failed: '失败',
    indeterminate: '结果待确认',
  };
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });
  return (
    <PageContainer title={t('operations.itemGrantRecords.title', '发放记录')}>
      <ProTable
        rowKey="id"
        search={false}
        params={{ kind }}
        toolBarRender={() => [
          <Select
            key="kind"
            aria-label="发放类型"
            style={{ width: 150 }}
            value={kind}
            onChange={setKind}
            options={[
              { value: '', label: '全部类型' },
              ...Object.entries(kinds).map(([value, label]) => ({
                value,
                label,
              })),
            ]}
          />,
        ]}
        request={async ({ current, pageSize, kind: selectedKind }) => {
          const response = await listItemGrants(
            current,
            pageSize,
            selectedKind,
          );
          return {
            data: response.data,
            total: response.meta.total,
            success: true,
          };
        }}
        columns={[
          {
            title: '类型',
            dataIndex: 'kind',
            renderText: (value) => kinds[value as keyof typeof kinds],
          },
          {
            title: '发放内容',
            dataIndex: 'item_id',
            render: (_, record) =>
              record.kind === 'zeny' ? 'Zeny' : `物品 #${record.item_id}`,
          },
          {
            title: t('operations.itemGrantRecords.characterId', '角色 ID'),
            dataIndex: 'char_id',
          },
          {
            title: t('operations.itemGrantRecords.amount', '数量'),
            dataIndex: 'amount',
          },
          {
            title: t('operations.itemGrantRecords.mailId', '邮件 ID'),
            dataIndex: 'mail_id',
          },
          {
            title: t('operations.itemGrantRecords.titleField', '邮件标题'),
            dataIndex: 'title',
          },
          {
            title: t('operations.itemGrantRecords.operator', '操作人'),
            render: (_, record) => {
              const requester = record.requester as
                | { username?: string; name?: string }
                | undefined;
              return String(
                requester?.username ??
                  requester?.name ??
                  (record.requested_game_account_id
                    ? `游戏账号 #${record.requested_game_account_id}`
                    : `后台账号 #${record.requested_by}`),
              );
            },
          },
          {
            title: t('common.status', '状态'),
            dataIndex: 'status',
            render: (_, record) => (
              <Tag
                color={
                  record.status === 'sent' || record.status === 'succeeded'
                    ? 'success'
                    : record.status === 'failed'
                      ? 'error'
                      : 'processing'
                }
              >
                {t(
                  `operations.itemGrantRecords.status.${String(record.status)}`,
                  statuses[String(record.status)] ?? String(record.status),
                )}
              </Tag>
            ),
          },
          {
            title: t('common.createdAt', '创建时间'),
            dataIndex: 'created_at',
            renderText: formatDateTime,
          },
        ]}
      />
    </PageContainer>
  );
}
