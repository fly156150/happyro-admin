import { TestBrowser } from '@@/testBrowser';
import { fireEvent, render, waitFor } from '@testing-library/react';
import React from 'react';
import { login } from '@/services/auth/index';

vi.mock('@/services/auth/index', () => ({
  login: vi.fn(),
  currentUser: vi.fn(),
  logout: vi.fn(),
}));

const mockedLogin = vi.mocked(login);

describe('Login Page', () => {
  beforeEach(() => {
    mockedLogin.mockReset();
  });

  it('shows only the internal username and password form', async () => {
    const rootContainer = render(
      <TestBrowser location={{ pathname: '/user/login' }} />,
    );

    expect(await rootContainer.findByText('HappyRO')).toBeInTheDocument();
    expect(
      rootContainer.getByText('《仙境传说 Online》管理后台'),
    ).toBeInTheDocument();
    expect(rootContainer.getByAltText('HappyRO')).toHaveAttribute(
      'src',
      '/images/ro-poring-1.webp',
    );
    expect(rootContainer.getByPlaceholderText('用户名')).toBeInTheDocument();
    expect(rootContainer.getByPlaceholderText('密码')).toBeInTheDocument();
    expect(rootContainer.getByText('保持登录')).toBeInTheDocument();
    expect(rootContainer.queryByText('手机号登录')).not.toBeInTheDocument();
    expect(rootContainer.queryByText('忘记密码')).not.toBeInTheDocument();
    expect(rootContainer.queryByText(/Ant Design Pro ©/)).not.toBeInTheDocument();
  });

  it('keeps the form content responsive on narrow screens', async () => {
    const rootContainer = render(
      <TestBrowser location={{ pathname: '/user/login' }} />,
    );

    const form = await rootContainer.findByRole('form');
    expect(form).toHaveStyle({ minWidth: 280, maxWidth: '75vw' });
  });

  it('submits credentials to the authentication service and shows API errors', async () => {
    mockedLogin.mockRejectedValue({
      response: { data: { message: '用户名或密码错误。' } },
    });
    const rootContainer = render(
      <TestBrowser location={{ pathname: '/user/login' }} />,
    );

    fireEvent.change(await rootContainer.findByPlaceholderText('用户名'), {
      target: { value: 'admin' },
    });
    fireEvent.change(rootContainer.getByPlaceholderText('密码'), {
      target: { value: 'wrong-password' },
    });
    fireEvent.click(rootContainer.getByRole('button', { name: '登录' }));

    await waitFor(() => {
      expect(mockedLogin).toHaveBeenCalledWith({
        username: 'admin',
        password: 'wrong-password',
        remember: true,
      });
    });
    expect(await rootContainer.findByText('用户名或密码错误。')).toBeInTheDocument();
  });
});
