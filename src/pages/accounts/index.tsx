import {
  CheckCircleOutlined,
  DeleteOutlined,
  DisconnectOutlined,
  DownOutlined,
  LockOutlined,
  StopOutlined,
  UserAddOutlined,
} from '@ant-design/icons';
import {
  type ActionType,
  PageContainer,
  type ProColumns,
  ProTable,
} from '@ant-design/pro-components';
import { useAccess, useIntl } from '@umijs/max';
import {
  App,
  Button,
  Dropdown,
  Form,
  Input,
  Modal,
  Select,
  Space,
  Tag,
} from 'antd';
import type { Key, RefObject } from 'react';
import { useRef, useState } from 'react';
import {
  batchAccounts,
  deleteAccount,
  listAccounts,
  type PlayerAccount,
  registerAccount,
  resetPassword,
  updateAccount,
} from '@/api/accounts';
import { sexMappings } from '@/data/game/sex';
import AccountEmailDialog from '@/features/accounts/AccountEmailDialog';
import { createRetryableMutation } from '@/utils/retryableMutation';
import DeletionTasks from './DeletionTasks';
import styles from './index.less';
import { credentialRule } from './validation';

type AccountAction = 'delete' | 'ban' | 'unban' | 'kick';
type Translate = (id: string, fallback: string) => string;

type AccountColumnsOptions = {
  canEdit: boolean;
  onDelete: (accountId: number) => void;
  onPasswordReset: (accountId: number) => void;
  onEditEmail: (account: PlayerAccount) => void;
  onToggleState: (account: PlayerAccount, reload?: () => void) => Promise<void>;
  t: Translate;
};

function accountColumns({
  canEdit,
  onDelete,
  onPasswordReset,
  onEditEmail,
  onToggleState,
  t,
}: AccountColumnsOptions): ProColumns<PlayerAccount>[] {
  return [
    {
      title: t('players.account.id', '账号 ID'),
      dataIndex: 'account_id',
      search: false,
    },
    { title: t('players.account.username', '用户名'), dataIndex: 'username' },
    { title: t('players.account.email', '邮箱'), dataIndex: 'email' },
    {
      title: t('players.account.group', '权限组'),
      dataIndex: 'group_id',
      search: false,
    },
    {
      title: t('players.account.status', '状态'),
      dataIndex: 'state',
      valueEnum: {
        0: { text: t('players.account.active', '正常'), status: 'Success' },
        1: { text: t('players.account.banned', '封禁'), status: 'Error' },
      },
      render: (_, row) => (
        <Tag color={row.state ? 'error' : 'success'}>
          {row.state
            ? t('players.account.banned', '封禁')
            : t('players.account.active', '正常')}
        </Tag>
      ),
    },
    {
      title: t('players.account.lastLogin', '最后登录'),
      dataIndex: 'lastlogin',
      search: false,
    },
    {
      title: t('common.actions', '操作'),
      valueType: 'option',
      hideInTable: !canEdit,
      render: (_, row, __, action) => (
        <Space>
          <Button type="link" size="small" onClick={() => onEditEmail(row)}>
            {t('players.account.editEmail', '修改邮箱')}
          </Button>
          <Button
            type="link"
            size="small"
            icon={row.state ? <CheckCircleOutlined /> : <StopOutlined />}
            onClick={() => onToggleState(row, action?.reload)}
          >
            {row.state
              ? t('players.account.unban', '解禁')
              : t('players.account.ban', '封禁')}
          </Button>
          <Button
            type="link"
            size="small"
            icon={<LockOutlined />}
            onClick={() => onPasswordReset(row.account_id)}
          >
            {t('players.account.resetPassword', '重置密码')}
          </Button>
          <Button
            type="link"
            size="small"
            icon={<DeleteOutlined />}
            danger
            onClick={() => onDelete(row.account_id)}
          >
            {t('common.delete', '删除')}
          </Button>
        </Space>
      ),
    },
  ];
}

type AccountToolbarProps = {
  selected: Key[];
  t: Translate;
  onAction: (action: AccountAction, ids: number[]) => void;
  onRegister: () => void;
};

function AccountToolbar({
  selected,
  t,
  onAction,
  onRegister,
}: AccountToolbarProps) {
  const menu = {
    items: [
      {
        key: 'delete',
        label: t('players.account.delete', '删除'),
        icon: <DeleteOutlined />,
        danger: true,
      },
      {
        key: 'ban',
        label: t('players.account.ban', '封禁'),
        icon: <StopOutlined />,
      },
      {
        key: 'unban',
        label: t('players.account.unban', '解禁'),
        icon: <CheckCircleOutlined />,
      },
      {
        key: 'kick',
        label: t('players.account.kick', '踢下线'),
        icon: <DisconnectOutlined />,
      },
    ],
    onClick: ({ key }: { key: string }) =>
      onAction(key as AccountAction, selected.map(Number)),
  };

  return (
    <Space>
      <Button type="primary" icon={<UserAddOutlined />} onClick={onRegister}>
        {t('players.account.register', '注册账号')}
      </Button>
      <Dropdown menu={menu} disabled={!selected.length}>
        <Button icon={<DownOutlined />}>
          {t('players.account.actions', '操作')}
        </Button>
      </Dropdown>
    </Space>
  );
}

type AccountDialogsProps = {
  passwordId?: number;
  registerOpen: boolean;
  t: Translate;
  onClosePassword: () => void;
  onCloseRegister: () => void;
  onPasswordReset: (accountId: number, password: string) => Promise<void>;
  onRegister: (values: Parameters<typeof registerAccount>[0]) => Promise<void>;
};

function AccountDialogs({
  passwordId,
  registerOpen,
  t,
  onClosePassword,
  onCloseRegister,
  onPasswordReset,
  onRegister,
}: AccountDialogsProps) {
  const [passwordForm] = Form.useForm();
  const [registerForm] = Form.useForm();
  const formLayout = {
    className: styles.dialogForm,
    size: 'large' as const,
    variant: 'outlined' as const,
    labelAlign: 'left' as const,
    labelCol: { xs: { span: 24 }, sm: { span: 5 } },
    wrapperCol: { xs: { span: 24 }, sm: { span: 19 } },
  };

  return (
    <>
      <Modal
        title={t('players.account.resetPassword', '重置密码')}
        open={Boolean(passwordId)}
        onCancel={onClosePassword}
        onOk={() => passwordForm.submit()}
        afterClose={() => passwordForm.resetFields()}
      >
        <Form
          {...formLayout}
          form={passwordForm}
          name="account-password-reset"
          onFinish={({ password }) =>
            passwordId && onPasswordReset(passwordId, password)
          }
        >
          <Form.Item
            name="password"
            label={t('players.account.newPassword', '新密码')}
            rules={[credentialRule('password')]}
          >
            <Input.Password
              placeholder="请输入 6–23 个字节的新密码"
              autoComplete="new-password"
            />
          </Form.Item>
        </Form>
      </Modal>
      <Modal
        title={t('players.account.register', '注册账号')}
        open={registerOpen}
        onCancel={onCloseRegister}
        onOk={() => registerForm.submit()}
        afterClose={() => registerForm.resetFields()}
      >
        <Form
          {...formLayout}
          form={registerForm}
          name="account-registration"
          onFinish={onRegister}
        >
          <Form.Item
            name="userid"
            label={t('players.account.username', '用户名')}
            rules={[credentialRule('account')]}
          >
            <Input placeholder="请输入 6–21 个字节的账号" autoComplete="off" />
          </Form.Item>
          <Form.Item
            name="user_pass"
            label={t('players.account.newPassword', '新密码')}
            rules={[credentialRule('password')]}
          >
            <Input.Password
              placeholder="请输入 6–23 个字节的密码"
              autoComplete="new-password"
            />
          </Form.Item>
          <Form.Item
            name="email"
            label={t('players.account.email', '邮箱')}
            rules={[{ required: true, type: 'email' }]}
          >
            <Input placeholder="请输入邮箱地址" type="email" />
          </Form.Item>
          <Form.Item
            name="sex"
            label={t('players.account.sex', '性别')}
            initialValue="M"
          >
            <Select
              options={Object.entries(sexMappings).map(([value, label]) => ({
                value,
                label: t(label, label),
              }))}
            />
          </Form.Item>
        </Form>
      </Modal>
    </>
  );
}

function reload(ref: RefObject<ActionType | null>) {
  ref.current?.reload();
}

export default function Accounts() {
  const access = useAccess();
  const [deletionRevision, setDeletionRevision] = useState(0);
  const [submitBatch] = useState(() =>
    createRetryableMutation<
      { action: AccountAction; account_ids: number[] },
      Awaited<ReturnType<typeof batchAccounts>>
    >((payload) =>
      batchAccounts(
        payload.action,
        payload.account_ids,
        payload.idempotency_key,
      ),
    ),
  );
  const intl = useIntl();
  const { message, modal } = App.useApp();
  const actionRef = useRef<ActionType>(null);
  const [selected, setSelected] = useState<Key[]>([]);
  const [emailAccount, setEmailAccount] = useState<PlayerAccount>();
  const [passwordId, setPasswordId] = useState<number>();
  const [registerOpen, setRegisterOpen] = useState(false);
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });
  const confirmAction = (action: AccountAction, ids: number[]) =>
    modal.confirm({
      title: t('players.account.confirmTitle', '请确认操作'),
      content: t(`players.account.confirm.${action}`, '确认执行此操作？'),
      okText: t('common.confirm', '确认'),
      cancelText: t('common.cancel', '取消'),
      okButtonProps: { danger: action === 'delete' },
      onOk: async () => {
        if (action === 'delete' && ids.length === 1) {
          await deleteAccount(ids[0]);
        } else {
          await submitBatch({
            action,
            account_ids: [...ids].sort((a, b) => a - b),
          });
        }
        if (action === 'delete') {
          message.info(
            t(
              'players.deletion.accepted',
              '删除任务已提交，请在下方查看处理结果',
            ),
          );
          setDeletionRevision((value) => value + 1);
        } else {
          message.success(t(`players.account.success.${action}`, '操作成功'));
        }
        setSelected([]);
        reload(actionRef);
      },
    });
  const columns = accountColumns({
    canEdit: access.canEditPlayers,
    t,
    onDelete: (accountId) => confirmAction('delete', [accountId]),
    onPasswordReset: setPasswordId,
    onEditEmail: setEmailAccount,
    onToggleState: async (account, reloadTable) => {
      await updateAccount(account.account_id, { state: account.state ? 0 : 1 });
      message.success(
        account.state
          ? t('players.account.unbanned', '已解禁')
          : t('players.account.bannedSuccess', '已封禁'),
      );
      reloadTable?.();
    },
  });

  return (
    <PageContainer title={t('players.accounts.title', '用户账号')}>
      <ProTable<PlayerAccount>
        headerTitle={
          access.canEditPlayers ? (
            <AccountToolbar
              selected={selected}
              t={t}
              onAction={confirmAction}
              onRegister={() => setRegisterOpen(true)}
            />
          ) : (
            t('players.accounts.title', '用户账号')
          )
        }
        actionRef={actionRef}
        rowKey="account_id"
        rowSelection={
          access.canEditPlayers
            ? { selectedRowKeys: selected, onChange: setSelected }
            : false
        }
        columns={columns}
        request={async (params) => {
          const result = await listAccounts(params);
          return { data: result.data, total: result.total, success: true };
        }}
        toolBarRender={() => []}
      />
      {access.canEditPlayers && (
        <DeletionTasks
          revision={deletionRevision}
          onCompleted={() => reload(actionRef)}
        />
      )}
      {access.canEditPlayers && emailAccount && (
        <AccountEmailDialog
          key={emailAccount.account_id}
          account={emailAccount}
          onClose={() => setEmailAccount(undefined)}
          onSaved={() => {
            setEmailAccount(undefined);
            reload(actionRef);
          }}
        />
      )}
      {access.canEditPlayers && (
        <AccountDialogs
          passwordId={passwordId}
          registerOpen={registerOpen}
          t={t}
          onClosePassword={() => setPasswordId(undefined)}
          onCloseRegister={() => setRegisterOpen(false)}
          onPasswordReset={async (accountId, password) => {
            try {
              await resetPassword(accountId, password);
              message.success(t('players.account.passwordReset', '密码已重置'));
              setPasswordId(undefined);
            } catch {
              // Shared request feedback reports the error; keep the form for retry.
            }
          }}
          onRegister={async (values) => {
            try {
              await registerAccount(values);
              message.success(t('players.account.registered', '账号已注册'));
              setRegisterOpen(false);
              reload(actionRef);
            } catch {
              // Shared request feedback reports the error; keep the form for retry.
            }
          }}
        />
      )}
    </PageContainer>
  );
}
