import { ClockCircleOutlined, UserOutlined } from '@ant-design/icons';
import { useIntl } from '@umijs/max';
import { Alert, App, Avatar, Form, Modal, Radio, Tag, theme } from 'antd';
import { useRef, useState } from 'react';
import { type PlayerAccount, setAccountGM } from '@/api/accounts';
import { accountGMGroups } from '@/data/game/accountGroups';
import styles from './AccountGMDialog.less';

export default function AccountGMDialog({
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
  const pending = useRef(false);
  const selected = Form.useWatch<number>('group_id', form);
  const { token } = theme.useToken();
  const { message } = App.useApp();
  const intl = useIntl();
  const t = (id: string, defaultMessage: string) =>
    intl.formatMessage({ id, defaultMessage });
  return (
    <Modal
      open
      title={t('players.account.gmTitle', '游戏权限设置')}
      width={560}
      okText={t('players.account.savePermission', '保存权限')}
      okButtonProps={{
        disabled: selected === undefined || selected === account.group_id,
      }}
      confirmLoading={saving}
      cancelButtonProps={{ disabled: saving }}
      closable={!saving}
      mask={{ closable: !saving }}
      onCancel={onClose}
      onOk={() => form.submit()}
    >
      <div
        className={styles.account}
        style={{ background: token.colorFillAlter }}
      >
        <Avatar
          size={44}
          icon={<UserOutlined />}
          style={{
            background: token.colorPrimaryBg,
            color: token.colorPrimary,
          }}
        />
        <div className={styles.identity}>
          <strong className={styles.name}>{account.username}</strong>
          <span
            className={styles.meta}
            style={{ color: token.colorTextSecondary }}
          >
            账号 ID · {account.account_id}
          </span>
        </div>
        <Tag color={account.group_id === 99 ? 'gold' : 'default'}>
          {account.group_id === 99
            ? '当前 · GM'
            : account.group_id === 0
              ? '当前 · 普通玩家'
              : `当前权限组 · ${account.group_id ?? '—'}`}
        </Tag>
      </div>
      <Form
        form={form}
        layout="vertical"
        name="account-gm-edit"
        initialValues={{
          group_id: accountGMGroups.some((g) => g.value === account.group_id)
            ? account.group_id
            : undefined,
        }}
        onFinish={async ({ group_id }: { group_id: number }) => {
          if (pending.current || account.group_id === undefined) return;
          pending.current = true;
          setSaving(true);
          try {
            await setAccountGM(account.account_id, group_id, account.group_id);
            message.success(
              t(
                'players.account.gmSaved',
                '权限已保存，请让该账号完整退出并重新登录',
              ),
            );
            onSaved();
          } catch {
            /* Shared request handling displays the server error. */
          } finally {
            pending.current = false;
            setSaving(false);
          }
        }}
      >
        <Form.Item
          name="group_id"
          label={t('players.account.targetPermission', '选择游戏身份')}
          rules={[
            {
              required: true,
              message: t('players.account.selectGroup', '请选择目标权限组'),
            },
          ]}
        >
          <Radio.Group
            className={styles.choices}
            disabled={saving || account.group_id === undefined}
          >
            {accountGMGroups.map((group) => (
              <Radio
                key={group.value}
                value={group.value}
                className={styles.choice}
                style={{
                  borderColor:
                    selected === group.value
                      ? token.colorPrimary
                      : token.colorBorder,
                  background:
                    selected === group.value
                      ? token.colorPrimaryBg
                      : token.colorBgContainer,
                }}
              >
                <span className={styles.choiceTitle}>
                  {t(
                    group.labelKey,
                    group.value === 99 ? '游戏管理员 GM' : '普通玩家',
                  )}
                </span>
                <span
                  className={styles.description}
                  style={{ color: token.colorTextSecondary }}
                >
                  {group.value === 99
                    ? t(
                        'players.account.gmDescription',
                        '开放完整游戏管理权限，可使用 GM 管理指令。',
                      )
                    : t(
                        'players.account.playerDescription',
                        '使用常规游戏权限，不具备 GM 管理能力。',
                      )}
                </span>
              </Radio>
            ))}
          </Radio.Group>
        </Form.Item>
      </Form>
      <p className={styles.scope} style={{ color: token.colorTextSecondary }}>
        {t(
          'players.account.gmScopeShort',
          '适用于该账号下的全部角色，不改变后台管理员权限。',
        )}
      </p>
      <Alert
        type="info"
        showIcon
        icon={<ClockCircleOutlined />}
        title={t('players.account.gmEffective', '重新登录后生效')}
        description={t(
          'players.account.gmLoginShort',
          '保存后请完整退出游戏账号再登录。仅切换角色不会生效，在线角色暂时保留原权限。',
        )}
      />
    </Modal>
  );
}
