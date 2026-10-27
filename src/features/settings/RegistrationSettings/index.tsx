import { useIntl } from '@umijs/max';
import { App, Button, Select, Space, Spin } from 'antd';
import { useEffect, useState } from 'react';
import {
  getRegistrationSettings,
  type RegistrationSettings as Settings,
  updateRegistrationSettings,
} from '@/api/settings/registration';
import { accountGMGroups } from '@/data/game/accountGroups';

export default function RegistrationSettings() {
  const intl = useIntl();
  const { message } = App.useApp();
  const [settings, setSettings] = useState<Settings>();
  const [group, setGroup] = useState<number>();
  const [saving, setSaving] = useState(false);
  const [failed, setFailed] = useState(false);
  const load = async () => {
    setFailed(false);
    try {
      const { data } = await getRegistrationSettings();
      setSettings(data);
      setGroup(data.default_group_id);
    } catch {
      setFailed(true);
    }
  };
  useEffect(() => {
    void load();
  }, []);
  if (failed)
    return <Button onClick={() => void load()}>重新加载注册设置</Button>;
  if (!settings || group === undefined) return <Spin />;
  return (
    <Space orientation="vertical" size="middle" style={{ paddingBottom: 24 }}>
      <label htmlFor="registration-default-group">新注册账号默认权限组</label>
      <Select
        id="registration-default-group"
        aria-label="新注册账号默认权限组"
        style={{ width: 240 }}
        disabled={!settings.editable || saving}
        value={group}
        onChange={setGroup}
        options={accountGMGroups.map((entry) => ({
          value: entry.value,
          label: intl.formatMessage({
            id: entry.labelKey,
            defaultMessage: entry.code,
          }),
        }))}
      />
      <Button
        type="primary"
        disabled={!settings.editable || group === settings.default_group_id}
        loading={saving}
        onClick={async () => {
          setSaving(true);
          try {
            await updateRegistrationSettings(group, settings.default_group_id);
            await load();
            message.success('注册设置已保存');
          } catch {
            // Reload after an uncertain response; never automatically resend a write.
            await load();
          } finally {
            setSaving(false);
          }
        }}
      >
        保存
      </Button>
    </Space>
  );
}
