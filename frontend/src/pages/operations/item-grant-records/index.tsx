import { PageContainer, ProTable } from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { Tag } from 'antd';
import { listItemGrants } from '@/services/operations/item-grants';

export default function ItemGrantRecords() {
  const intl = useIntl();
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });
  return (
    <PageContainer
      title={t('operations.itemGrantRecords.title', '物品发放记录')}
    >
      <ProTable
        rowKey="id"
        search={false}
        request={async ({ current }) => {
          const response = await listItemGrants(current);
          return {
            data: response.data,
            total: response.meta.total,
            success: true,
          };
        }}
        columns={[
          { title: 'ID', dataIndex: 'id', width: 80 },
          {
            title: t('operations.itemGrantRecords.itemId', '物品 ID'),
            dataIndex: 'item_id',
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
              return String(requester?.username ?? requester?.name ?? '-');
            },
          },
          {
            title: t('common.status', '状态'),
            dataIndex: 'status',
            render: (_, record) => (
              <Tag color={record.status === 'sent' ? 'success' : record.status === 'failed' ? 'error' : 'processing'}>
                {t(`operations.itemGrantRecords.status.${String(record.status)}`, String(record.status))}
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
