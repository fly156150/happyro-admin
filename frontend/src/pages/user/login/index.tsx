import { LockOutlined, UserOutlined } from '@ant-design/icons';
import {
  LoginForm,
  ProFormCheckbox,
  ProFormText,
} from '@ant-design/pro-components';
import { Helmet, SelectLang, useModel } from '@umijs/max';
import { Alert, App } from 'antd';
import { createStyles } from 'antd-style';
import React, { startTransition, useState } from 'react';
import { login } from '@/services/auth/index';
import Settings from '../../../../config/defaultSettings';

const getSafeRedirectUrl = (redirect: string | null): string => {
  if (!redirect?.startsWith('/') || redirect.startsWith('//')) return '/';

  try {
    const parsed = new URL(redirect, window.location.origin);
    if (parsed.origin !== window.location.origin) return '/';
    return `${parsed.pathname}${parsed.search}${parsed.hash}`;
  } catch {
    return '/';
  }
};

const useStyles = createStyles(({ token }) => ({
  lang: {
    position: 'fixed',
    zIndex: 1,
    top: 8,
    right: 16,
    width: 42,
    height: 42,
    lineHeight: '42px',
    borderRadius: token.borderRadius,
    ':hover': {
      backgroundColor: token.colorBgTextHover,
    },
  },
  container: {
    display: 'flex',
    width: '100%',
    height: '100vh',
    minHeight: 560,
    flexDirection: 'column',
    boxSizing: 'border-box',
    overflowY: 'auto',
    overflowX: 'hidden',
    backgroundImage:
      "url('https://mdn.alipayobjects.com/yuyan_qk0oxh/afts/img/V-_oS6r-i7wAAAAAAAAAAAAAFl94AQBr')",
    backgroundSize: '100% 100%',
  },
  content: {
    display: 'flex',
    flex: 1,
    alignItems: 'center',
    boxSizing: 'border-box',
    padding: '32px 0',
    '.ant-pro-form-login-container': {
      justifyContent: 'center',
    },
  },
}));

const Login: React.FC = () => {
  const [error, setError] = useState<string>();
  const { initialState, setInitialState } = useModel('@@initialState');
  const { styles } = useStyles();
  const { message } = App.useApp();

  const handleSubmit = async (values: {
    username: string;
    password: string;
    remember?: boolean;
  }) => {
    setError(undefined);

    try {
      await login({
        username: values.username,
        password: values.password,
        remember: values.remember ?? false,
      });
      const user = await initialState?.fetchUserInfo?.();

      if (!user) {
        setError('无法读取登录用户信息，请重试。');
        return;
      }

      startTransition(() => {
        setInitialState((state) => ({ ...state, currentUser: user }));
      });
      message.success('登录成功');
      const parameters = new URL(window.location.href).searchParams;
      window.location.href = getSafeRedirectUrl(parameters.get('redirect'));
    } catch (requestError) {
      const responseMessage = (
        requestError as { response?: { data?: { message?: string } } }
      ).response?.data?.message;
      setError(responseMessage ?? '登录失败，请稍后重试。');
    }
  };

  return (
    <div className={styles.container}>
      <Helmet>
        <title>登录 - {Settings.title}</title>
      </Helmet>
      <div className={styles.lang} data-lang>
        {SelectLang && <SelectLang />}
      </div>
      <div className={styles.content}>
        <LoginForm
          contentStyle={{ minWidth: 280, maxWidth: '75vw' }}
          logo={<img alt="HappyRO" src="/images/ro-poring-1.webp" />}
          title="HappyRO"
          subTitle="《仙境传说 Online》管理后台"
          initialValues={{ remember: true }}
          onFinish={handleSubmit}
        >
          {error && (
            <Alert
              style={{ marginBottom: 24 }}
              title={error}
              type="error"
              showIcon
            />
          )}
          <ProFormText
            name="username"
            fieldProps={{
              size: 'large',
              autoComplete: 'username',
              prefix: <UserOutlined />,
            }}
            placeholder="用户名"
            rules={[{ required: true, message: '请输入用户名' }]}
          />
          <ProFormText.Password
            name="password"
            fieldProps={{
              size: 'large',
              autoComplete: 'current-password',
              prefix: <LockOutlined />,
            }}
            placeholder="密码"
            rules={[{ required: true, message: '请输入密码' }]}
          />
          <div style={{ marginBottom: 24 }}>
            <ProFormCheckbox noStyle name="remember">
              保持登录
            </ProFormCheckbox>
          </div>
        </LoginForm>
      </div>
    </div>
  );
};

export default Login;
