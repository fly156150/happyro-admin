import { useIntl } from '@umijs/max';
import { App, Form, Input, Modal } from 'antd';
import { useState } from 'react';
import { type PlayerAccount, updateAccount } from '@/api/accounts';

export default function AccountEmailDialog({
  account,
  onClose,
  onSaved,
}: {
  account: PlayerAccount;
  onClose: () => void;
  onSaved: () => void;
}) {
  const [form] = Form.useForm();
  const [saving, setSaving] = useState(false);
  const { message } = App.useApp();
  const intl = useIntl();
  const t = (id: string, defaultMessage: string) =>
    intl.formatMessage({ id, defaultMessage });
  return (
    <Modal
      open
      title={t('players.account.editEmail', '修改邮箱')}
      confirmLoading={saving}
      cancelButtonProps={{ disabled: saving }}
      closable={!saving}
      maskClosable={!saving}
      onCancel={onClose}
      onOk={() => form.submit()}
    >
      <Form
        form={form}
        layout="vertical"
        initialValues={{ email: account.email }}
        onFinish={async ({ email }: { email: string }) => {
          if (saving) return;
          setSaving(true);
          try {
            await updateAccount(account.account_id, { email });
            message.success(t('players.account.emailUpdated', '邮箱已更新'));
            onSaved();
          } catch {
            // The shared request handler displays the server error.
          } finally {
            setSaving(false);
          }
        }}
      >
        <Form.Item
          name="email"
          label={t('players.account.email', '邮箱')}
          rules={[
            { required: true, type: 'email' },
            {
              validator: (_, value: string) =>
                !value || new TextEncoder().encode(value).length <= 39
                  ? Promise.resolve()
                  : Promise.reject(
                      new Error(
                        t('players.account.emailTooLong', '邮箱最多 39 个字节'),
                      ),
                    ),
            },
          ]}
        >
          <Input autoComplete="email" disabled={saving} />
        </Form.Item>
      </Form>
    </Modal>
  );
}
