import { LockOutlined, LogoutOutlined } from '@ant-design/icons';
import { history, useModel } from '@umijs/max';
import type { MenuProps } from 'antd';
import { App, Spin } from 'antd';
import React, { startTransition, useState } from 'react';
import { currentUser as fetchCurrentUser, logout } from '@/api/auth/index';
import HeaderDropdown from '../HeaderDropdown';
import ChangePasswordModal from './ChangePasswordModal';

type GlobalHeaderRightProps = {
  children?: React.ReactNode;
};

const menuItems: MenuProps['items'] = [
  {
    key: 'logout',
    icon: <LogoutOutlined />,
    label: '退出登录',
  },
];

const loginOut = async () => {
  try {
    await logout();
  } catch {
    // Local logout has already cleared user state; redirect should still proceed.
  }
  const { search, pathname } = window.location;
  const urlParams = new URL(window.location.href).searchParams;
  const searchParams = new URLSearchParams({
    redirect: pathname + search,
  });
  const redirect = urlParams.get('redirect');
  if (window.location.pathname !== '/user/login' && !redirect) {
    history.replace({
      pathname: '/user/login',
      search: searchParams.toString(),
    });
  }
};

export const AvatarDropdown: React.FC<GlobalHeaderRightProps> = ({
  children,
}) => {
  const [passwordOpen, setPasswordOpen] = useState(false);
  const { message } = App.useApp();
  const { initialState, setInitialState } = useModel('@@initialState');

  const onMenuClick: MenuProps['onClick'] = (event) => {
    const { key } = event;
    if (key === 'password') {
      setPasswordOpen(true);
      return;
    }
    if (key === 'logout') {
      startTransition(() => {
        setInitialState((s) => ({ ...s, currentUser: undefined }));
      });
      loginOut();
      return;
    }
  };

  if (!initialState) {
    return <Spin size="small" />;
  }

  const { currentUser } = initialState;

  if (!currentUser) {
    return <Spin size="small" />;
  }

  return (
    <>
      <HeaderDropdown
        placement="bottomRight"
        onOpenChange={async (open) => {
          if (!open) return;
          try {
            const result = await fetchCurrentUser();
            setInitialState((s) =>
              s?.currentUser?.id === result.data.id
                ? { ...s, currentUser: result.data }
                : s,
            );
            if (!result.data.passwordChangeEnabled) setPasswordOpen(false);
          } catch {
            /* The request error handler reports authentication failures. */
          }
        }}
        menu={{
          selectedKeys: [],
          onClick: onMenuClick,
          items: currentUser.passwordChangeEnabled
            ? [
                { key: 'password', icon: <LockOutlined />, label: '修改密码' },
                ...menuItems,
              ]
            : menuItems,
        }}
        arrow
      >
        {children}
      </HeaderDropdown>
      <ChangePasswordModal
        open={passwordOpen && currentUser.passwordChangeEnabled}
        onCancel={() => setPasswordOpen(false)}
        onSuccess={() => {
          setPasswordOpen(false);
          message.success('密码已修改，请重新登录');
          setInitialState((s) => ({ ...s, currentUser: undefined }));
          history.replace('/user/login');
        }}
      />
    </>
  );
};
