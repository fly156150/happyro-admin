import {
  type ActionType,
  type ProColumns,
  ProTable,
} from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { App, Button, Tag } from 'antd';
import { useRef, useState } from 'react';
import {
  type AccountDeletion,
  listAccountDeletions,
  retryAccountDeletion,
} from '@/api/accounts';

const labels: Record<AccountDeletion['status'], string> = {
  queued: '等待处理',
  disconnecting: '等待下线与存档',
  deleting: '正在清理',
  failed: '删除失败',
  completed: '删除完成',
};

export default function DeletionTasks({
  revision,
  onCompleted,
}: {
  revision: number;
  onCompleted?: () => void;
}) {
  const completed = useRef(new Set<number>());
  const intl = useIntl();
  const { message } = App.useApp();
  const actionRef = useRef<ActionType>(null);
  const [retrying, setRetrying] = useState<number>();
  const [loadFailed, setLoadFailed] = useState(false);
  const t = (id: string, defaultMessage: string) =>
    intl.formatMessage({ id, defaultMessage });
  const retry = async (id: number) => {
    setRetrying(id);
    try {
      await retryAccountDeletion(id);
      message.success(
        t('players.deletion.retryAccepted', '已重新提交删除任务'),
      );
      actionRef.current?.reload();
    } catch {
      // The shared request handler displays the server's error message.
    } finally {
      setRetrying(undefined);
    }
  };
  const columns: ProColumns<AccountDeletion>[] = [
    { title: t('players.account.id', '账号 ID'), dataIndex: 'account_id' },
    {
      title: t('players.account.username', '用户名'),
      dataIndex: 'account_name',
    },
    {
      title: t('players.account.status', '状态'),
      dataIndex: 'status',
      render: (_, row) => (
        <Tag
          color={
            row.status === 'failed'
              ? 'error'
              : row.status === 'completed'
                ? 'success'
                : 'processing'
          }
        >
          {t(`players.deletion.status.${row.status}`, labels[row.status])}
        </Tag>
      ),
    },
    {
      title: t('players.deletion.progress', '已清理角色'),
      render: (_, row) => `${row.deleted_characters} / ${row.total_characters}`,
    },
    {
      title: t('players.deletion.detail', '处理说明'),
      render: (_, row) =>
        row.status === 'failed'
          ? t(
              `players.deletion.error.${row.error_code}`,
              row.error_code === 'protected_account'
                ? '服务器账号不能删除'
                : '清理未完成，请检查游戏服务状态后重试',
            )
          : row.status === 'disconnecting'
            ? t('players.deletion.waiting', '等待游戏服务确认存档完成')
            : '—',
    },
    {
      title: t('players.deletion.created', '提交时间'),
      dataIndex: 'created_at',
    },
    {
      title: t('common.actions', '操作'),
      valueType: 'option',
      render: (_, row) =>
        row.status === 'failed' && row.error_code !== 'protected_account' ? (
          <Button
            key="retry"
            type="link"
            loading={retrying === row.account_id}
            disabled={retrying !== undefined}
            onClick={() => void retry(row.account_id)}
          >
            {t('players.deletion.retry', '重试删除')}
          </Button>
        ) : null,
    },
  ];
  return (
    <ProTable<AccountDeletion>
      headerTitle={t('players.deletion.title', '账号删除任务')}
      actionRef={actionRef}
      rowKey="account_id"
      columns={columns}
      search={false}
      params={{ revision }}
      polling={loadFailed ? 0 : 3000}
      pagination={{ defaultPageSize: 10 }}
      onRequestError={() => setLoadFailed(true)}
      request={async (params) => {
        const result = await listAccountDeletions(params);
        let newlyCompleted = false;
        for (const row of result.data) {
          if (
            row.status === 'completed' &&
            !completed.current.has(row.account_id)
          ) {
            completed.current.add(row.account_id);
            newlyCompleted = true;
          }
        }
        if (newlyCompleted) onCompleted?.();
        setLoadFailed(false);
        return { ...result, success: true };
      }}
    />
  );
}
