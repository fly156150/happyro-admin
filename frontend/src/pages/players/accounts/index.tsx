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
  PageContainer,
  ProTable,
  type ActionType,
  type ProColumns,
} from '@ant-design/pro-components';
import { App, Button, Dropdown, Form, Input, Modal, Space, Tag } from 'antd';
import type { MenuProps } from 'antd';
import { useIntl } from '@umijs/max';
import { useRef, useState } from 'react';
import type { Key } from 'react';
import {
  batchAccounts,
  deleteAccount,
  listAccounts,
  registerAccount,
  resetPassword,
  updateAccount,
  type PlayerAccount,
} from '@/services/players';

export default function Accounts() {
  const intl = useIntl();
  const { message, modal } = App.useApp();
  const actionRef = useRef<ActionType>(null);
  const [selected, setSelected] = useState<Key[]>([]);
  const [passwordId, setPasswordId] = useState<number>();
  const [registerOpen, setRegisterOpen] = useState(false);
  const [form] = Form.useForm();
  const [registerForm] = Form.useForm();
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });
  const confirmAction = (
    action: 'delete' | 'ban' | 'unban' | 'kick',
    ids: number[],
  ) =>
    modal.confirm({
      title: t('players.account.confirmTitle', '请确认操作'),
      content: t(`players.account.confirm.${action}`, '确认执行此操作？'),
      okText: t('common.confirm', '确认'),
      cancelText: t('common.cancel', '取消'),
      okButtonProps: { danger: action === 'delete' },
      onOk: async () => {
        if (action === 'delete' && ids.length === 1)
          await deleteAccount(ids[0]);
        else await batchAccounts(action, ids);
        message.success(t(`players.account.success.${action}`, '操作成功'));
        setSelected([]);
        actionRef.current?.reload();
      },
    });
  const columns: ProColumns<PlayerAccount>[] = [
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
      render: (_, row, __, action) => (
        <Space>
          <Button
            type="link"
            size="small"
            icon={row.state ? <CheckCircleOutlined /> : <StopOutlined />}
            onClick={async () => {
              await updateAccount(row.account_id, { state: row.state ? 0 : 1 });
              message.success(
                row.state
                  ? t('players.account.unbanned', '已解禁')
                  : t('players.account.bannedSuccess', '已封禁'),
              );
              action?.reload();
            }}
          >
            {row.state
              ? t('players.account.unban', '解禁')
              : t('players.account.ban', '封禁')}
          </Button>
          <Button
            type="link"
            size="small"
            icon={<LockOutlined />}
            onClick={() => {
              setPasswordId(row.account_id);
              form.resetFields();
            }}
          >
            {t('players.account.resetPassword', '重置密码')}
          </Button>
          <Button
            type="link"
            size="small"
            icon={<DeleteOutlined />}
            danger
            onClick={() => confirmAction('delete', [row.account_id])}
          >
            {t('common.delete', '删除')}
          </Button>
        </Space>
      ),
    },
  ];
  const batchMenu: MenuProps = {
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
    onClick: ({ key }) =>
      confirmAction(
        key as 'delete' | 'ban' | 'unban' | 'kick',
        selected.map(Number),
      ),
  };
  const toolbar = (
    <Dropdown menu={batchMenu} disabled={!selected.length}>
      <Button icon={<DownOutlined />}>
        {t('players.account.actions', '操作')}
      </Button>
    </Dropdown>
  );
  const panelActions = (
    <Space>
      <Button
        type="primary"
        icon={<UserAddOutlined />}
        onClick={() => {
          registerForm.resetFields();
          setRegisterOpen(true);
        }}
      >
        {t('players.account.register', '注册账号')}
      </Button>
      {toolbar}
    </Space>
  );
  return (
    <PageContainer title={t('players.accounts.title', '用户账号')}>
      <ProTable<PlayerAccount>
        headerTitle={panelActions}
        actionRef={actionRef}
        rowKey="account_id"
        rowSelection={{ selectedRowKeys: selected, onChange: setSelected }}
        columns={columns}
        request={async (params) => {
          const result = await listAccounts(params);
          return { data: result.data, total: result.total, success: true };
        }}
        toolBarRender={() => []}
      />
      <Modal
        title={t('players.account.resetPassword', '重置密码')}
        open={!!passwordId}
        onCancel={() => setPasswordId(undefined)}
        onOk={() => form.submit()}
      >
        <Form
          form={form}
          onFinish={async ({ password }) => {
            if (!passwordId) return;
            await resetPassword(passwordId, password);
            message.success(t('players.account.passwordReset', '密码已重置'));
            setPasswordId(undefined);
          }}
        >
          <Form.Item
            name="password"
            label={t('players.account.newPassword', '新密码')}
            rules={[{ required: true, min: 8 }]}
          >
            <Input.Password />
          </Form.Item>
        </Form>
      </Modal>
      <Modal
        title={t('players.account.register', '注册账号')}
        open={registerOpen}
        onCancel={() => setRegisterOpen(false)}
        onOk={() => registerForm.submit()}
      >
        <Form
          form={registerForm}
          onFinish={async (values) => {
            await registerAccount(values);
            message.success(t('players.account.registered', '账号已注册'));
            setRegisterOpen(false);
            actionRef.current?.reload();
          }}
        >
          <Form.Item
            name="userid"
            label={t('players.account.username', '用户名')}
            rules={[{ required: true, min: 4, max: 23 }]}
          >
            <Input />
          </Form.Item>
          <Form.Item
            name="user_pass"
            label={t('players.account.newPassword', '新密码')}
            rules={[{ required: true, min: 8 }]}
          >
            <Input.Password />
          </Form.Item>
          <Form.Item
            name="email"
            label={t('players.account.email', '邮箱')}
            rules={[{ required: true, type: 'email' }]}
          >
            <Input />
          </Form.Item>
          <Form.Item
            name="sex"
            label={t('players.account.sex', '性别')}
            initialValue="M"
          >
            <Input />
          </Form.Item>
        </Form>
      </Modal>
    </PageContainer>
  );
}
