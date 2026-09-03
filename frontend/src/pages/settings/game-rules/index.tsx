import {
  PageContainer,
  ProForm,
  ProFormDigit,
  ProFormText,
} from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { App, Card, Flex, Spin, Typography } from 'antd';
import { useEffect, useState } from 'react';
import {
  type GameRuleSettings,
  getGameRuleSettings,
  updateGameRuleSettings,
} from '@/services/settings/game-rules';

type Translate = (id: string, fallback: string) => string;

function ruleLabel(key: string, t: Translate): string {
  return t(`settings.gameRules.key.${key}`, key);
}

export default function GameRulesSettingPage() {
  const intl = useIntl();
  const { message } = App.useApp();
  const [settings, setSettings] = useState<GameRuleSettings>();
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });

  useEffect(() => {
    getGameRuleSettings()
      .then(({ data }) => setSettings(data))
      .catch(() =>
        message.error(
          t('settings.gameRules.loadFailed', '游戏规则设置加载失败'),
        ),
      );
  }, []);

  return (
    <PageContainer title={t('settings.gameRules.title', '游戏规则')}>
      <Card variant="borderless">
        {!settings ? (
          <Spin />
        ) : (
          <ProForm
            initialValues={settings.values}
            submitter={{
              searchConfig: { submitText: t('common.save', '保存') },
            }}
            onFinish={async (values) => {
              const changes = Object.fromEntries(
                Object.keys(settings.definitions).map((key) => [
                  key,
                  Number(values[key]),
                ]),
              );
              await updateGameRuleSettings({
                changes,
                reason: String(values.reason),
              });
              const { data } = await getGameRuleSettings();
              setSettings(data);
              message.success(
                t('settings.gameRules.saved', '游戏规则设置已保存'),
              );
              return true;
            }}
          >
            {Object.values(settings.definitions).map((definition) => (
              <ProFormDigit
                key={definition.key}
                name={definition.key}
                label={ruleLabel(definition.key, t)}
                min={definition.minimum}
                max={definition.maximum}
                fieldProps={{ precision: 0 }}
                addonAfter={t('settings.gameRules.unit.percent', '%')}
                extra={
                  <Flex gap={8} wrap>
                    <Typography.Text type="secondary">
                      {t('settings.gameRules.source', '来源')}:{' '}
                      {definition.source}
                    </Typography.Text>
                    <Typography.Text type="secondary">
                      {definition.minimum} - {definition.maximum}
                    </Typography.Text>
                  </Flex>
                }
                width="md"
                rules={[{ required: true }]}
              />
            ))}
            <ProFormText
              name="reason"
              label={t('settings.gameRules.reason', '修改原因')}
              rules={[{ required: true }]}
              width="md"
            />
          </ProForm>
        )}
      </Card>
    </PageContainer>
  );
}
