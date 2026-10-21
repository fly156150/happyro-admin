import { PageContainer, ProTable, type ProColumns } from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { listLoginLogs } from '@/api/accounts/login-logs';

type LoginLog = Record<string, unknown>;

function formatLogMessage(value: unknown, translate: (id: string, fallback: string) => string) {
  return String(value ?? '').trim().toLowerCase() === 'login ok' ? translate('players.loginLog.loginSuccess', '登录成功') : String(value ?? '');
}

export default function LoginLogs() {
  const intl = useIntl(); const t = (id: string, fallback: string) => intl.formatMessage({ id, defaultMessage: fallback });
  const columns: ProColumns<LoginLog>[] = [{ title: t('players.loginLog.time', '时间'), dataIndex: 'time', search: false }, { title: t('players.account.username', '用户名'), dataIndex: 'username' }, { title: t('players.loginLog.ip', 'IP 地址'), dataIndex: 'ip', search: false }, { title: t('players.loginLog.code', '结果码'), dataIndex: 'rcode', search: false }, { title: t('players.loginLog.message', '说明'), dataIndex: 'log', search: false, render: (_, row) => formatLogMessage(row.log, t) }];
  // Native login logs have no primary key; identical events can share every field.
  // This read-only page uses unique positions in each fetched page for row identity.
  return <PageContainer title={t('players.loginLogs.title', '登录日志')}><ProTable<LoginLog> rowKey="_rowKey" columns={columns} request={async (params) => { const result = await listLoginLogs(params); return { data: result.data.map((row, index) => ({ ...row, _rowKey: `${params.current ?? 1}-${index}` })), total: result.total, success: true }; }} /></PageContainer>;
}
