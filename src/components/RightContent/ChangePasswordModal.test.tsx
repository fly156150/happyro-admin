import {
  cleanup,
  fireEvent,
  render,
  screen,
  waitFor,
} from '@testing-library/react';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import ChangePasswordModal from './ChangePasswordModal';

const api = vi.hoisted(() => ({ change: vi.fn() }));
vi.mock('@/api/auth', () => ({ changePassword: api.change }));
afterEach(cleanup);
beforeEach(() => {
  api.change.mockReset();
});
function fill(current = 'old-password', next = 'new-password', confirm = next) {
  fireEvent.change(screen.getByLabelText('当前密码'), {
    target: { value: current },
  });
  fireEvent.change(screen.getByLabelText('新密码'), {
    target: { value: next },
  });
  fireEvent.change(screen.getByLabelText('确认新密码'), {
    target: { value: confirm },
  });
}
it('submits once while pending and only succeeds after the API confirms', async () => {
  let resolve!: () => void;
  api.change.mockReturnValue(
    new Promise<void>((r) => {
      resolve = r;
    }),
  );
  const success = vi.fn();
  render(<ChangePasswordModal open onCancel={vi.fn()} onSuccess={success} />);
  fill();
  fireEvent.click(screen.getByRole('button', { name: /保\s*存/ }));
  await waitFor(() => expect(api.change).toHaveBeenCalledTimes(1));
  fireEvent.click(screen.getByRole('button', { name: /保\s*存/ }));
  expect(api.change).toHaveBeenCalledWith({
    currentPassword: 'old-password',
    newPassword: 'new-password',
    confirmPassword: 'new-password',
  });
  expect(success).not.toHaveBeenCalled();
  resolve();
  await waitFor(() => expect(success).toHaveBeenCalledOnce());
});
it('rejects mismatched or unchanged passwords without a request', async () => {
  render(<ChangePasswordModal open onCancel={vi.fn()} onSuccess={vi.fn()} />);
  fill('old-password', 'new-password', 'different');
  fireEvent.click(screen.getByRole('button', { name: /保\s*存/ }));
  await screen.findByText('两次输入的新密码不一致');
  expect(api.change).not.toHaveBeenCalled();
  fill('old-password', 'old-password');
  fireEvent.click(screen.getByRole('button', { name: /保\s*存/ }));
  await screen.findByText('新密码不能与当前密码相同');
  expect(api.change).not.toHaveBeenCalled();
});
it('shows a server rejection without logging out', async () => {
  api.change.mockRejectedValue({
    response: { data: { message: '修改密码功能已关闭。' } },
  });
  const success = vi.fn();
  render(<ChangePasswordModal open onCancel={vi.fn()} onSuccess={success} />);
  fill();
  fireEvent.click(screen.getByRole('button', { name: /保\s*存/ }));
  await screen.findByText('修改密码功能已关闭。');
  expect(success).not.toHaveBeenCalled();
});
