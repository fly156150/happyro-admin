import {
  PageContainer,
  ProForm,
  ProFormSelect,
} from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { App, Card, Spin } from 'antd';
import { useEffect, useState } from 'react';
import {
  type GameDataSettingInput,
  type GameDataSettings,
  getGameDataSettings,
  updateGameDataSettings,
} from '@/services/settings/game-data';

export default function GameDataSettingPage() {
  const intl = useIntl();
  const { message } = App.useApp();
  const [settings, setSettings] = useState<GameDataSettings>();
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });

  useEffect(() => {
    getGameDataSettings()
      .then(({ data }) => setSettings(data))
      .catch(() =>
        message.error(
          t('settings.gameData.loadFailed', '游戏资料设置加载失败'),
        ),
      );
  }, []);

  return (
    <PageContainer title={t('settings.gameData.title', '游戏资料')}>
      <Card variant="borderless">
        {!settings ? (
          <Spin />
        ) : (
          <ProForm<GameDataSettingInput>
            initialValues={settings}
            submitter={{ searchConfig: { submitText: t('common.save', '保存') } }}
            onFinish={async (values) => {
              const { data } = await updateGameDataSettings(values);
              setSettings({ ...settings, ...data });
              message.success(
                t('settings.gameData.saved', '游戏资料设置已保存'),
              );
              return true;
            }}
          >
            <ProFormSelect
              name="clientVersion"
              label={t('gameData.item.clientVersion', '客户端资源')}
              options={settings.available.client.map((version) => ({
                label: version,
                value: version,
              }))}
              rules={[{ required: true }]}
              width="md"
            />
            <ProFormSelect
              name="serverVersion"
              label={t('gameData.item.serverVersion', '服务端版本')}
              options={settings.available.server.map((version) => ({
                label: version.slice(0, 10),
                value: version,
              }))}
              rules={[{ required: true }]}
              width="md"
            />
          </ProForm>
        )}
      </Card>
    </PageContainer>
  );
}
