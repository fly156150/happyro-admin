import { Alert, Form, Input, Modal } from 'antd';
import { useRef, useState } from 'react';
import { changePassword } from '@/api/auth';

type Values = {
  currentPassword: string;
  newPassword: string;
  confirmPassword: string;
};
type Props = { open: boolean; onCancel: () => void; onSuccess: () => void };

export default function ChangePasswordModal({
  open,
  onCancel,
  onSuccess,
}: Props) {
  const [form] = Form.useForm<Values>();
  const [pending, setPending] = useState(false);
  const [error, setError] = useState('');
  const submitting = useRef(false);
  const submit = async (values: Values) => {
    if (submitting.current) return;
    submitting.current = true;
    setPending(true);
    setError('');
    try {
      await changePassword(values);
      form.resetFields();
      onSuccess();
    } catch (failure) {
      const detail = failure as {
        response?: { data?: { message?: string; errorMessage?: string } };
      };
      setError(
        detail.response?.data?.message ||
          detail.response?.data?.errorMessage ||
          '修改结果未确认，请尝试重新登录；如仍使用旧密码，请稍后重试。',
      );
    } finally {
      submitting.current = false;
      setPending(false);
    }
  };
  return (
    <Modal
      title="修改密码"
      open={open}
      okText="保存"
      cancelText="取消"
      confirmLoading={pending}
      closable={!pending}
      keyboard={!pending}
      mask={{ closable: !pending }}
      cancelButtonProps={{ disabled: pending }}
      onOk={() => form.submit()}
      onCancel={onCancel}
      destroyOnHidden
      afterClose={() => {
        form.resetFields();
        setError('');
      }}
    >
      {error && (
        <Alert
          type="error"
          showIcon
          title={error}
          style={{ marginBottom: 16 }}
        />
      )}
      <Form
        form={form}
        layout="vertical"
        onFinish={submit}
        preserve={false}
        disabled={pending}
      >
        <Form.Item
          label="当前密码"
          name="currentPassword"
          rules={[{ required: true, message: '请输入当前密码' }]}
        >
          <Input.Password autoComplete="current-password" maxLength={255} />
        </Form.Item>
        <Form.Item
          label="新密码"
          name="newPassword"
          dependencies={['currentPassword']}
          rules={[
            { required: true, message: '请输入新密码' },
            ({ getFieldValue }) => ({
              validator: async (_, value: string) => {
                if (!value) return;
                const size = new TextEncoder().encode(value).length;
                if (size < 5 || size > 255 || value.includes('\0'))
                  throw new Error('密码需为 5～255 字节，不能包含空字符');
                if (value === getFieldValue('currentPassword'))
                  throw new Error('新密码不能与当前密码相同');
              },
            }),
          ]}
        >
          <Input.Password autoComplete="new-password" maxLength={255} />
        </Form.Item>
        <Form.Item
          label="确认新密码"
          name="confirmPassword"
          dependencies={['newPassword']}
          rules={[
            { required: true, message: '请再次输入新密码' },
            ({ getFieldValue }) => ({
              validator: async (_, value: string) => {
                if (value && value !== getFieldValue('newPassword'))
                  throw new Error('两次输入的新密码不一致');
              },
            }),
          ]}
        >
          <Input.Password autoComplete="new-password" maxLength={255} />
        </Form.Item>
      </Form>
    </Modal>
  );
}
