import {
  PageContainer,
  ProForm,
  ProFormDigit,
  ProFormRadio,
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

const normalDropRateKeys = [
  'item_rate_common',
  'item_rate_heal',
  'item_rate_use',
  'item_rate_equip',
  'item_rate_card',
] as const;
const mvpDropRateKeys = [
  'item_rate_common_mvp',
  'item_rate_heal_mvp',
  'item_rate_use_mvp',
  'item_rate_equip_mvp',
  'item_rate_card_mvp',
] as const;
const experienceRateKeys = ['base_exp_rate', 'job_exp_rate'] as const;

function ruleLabel(key: string, t: Translate): string {
  return t(`settings.gameRules.key.${key}`, key);
}

function ruleExtra(
  definition: GameRuleSettings['definitions'][string],
  t: Translate
) {
  return (
    <Flex gap={8} wrap>
      <Typography.Text type="secondary">
        {t('settings.gameRules.source', '来源')}: {definition.source}
      </Typography.Text>
      <Typography.Text type="secondary">
        {definition.minimum} - {definition.maximum}
      </Typography.Text>
    </Flex>
  );
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
          t('settings.gameRules.loadFailed', '游戏规则设置加载失败')
        )
      );
  }, []);

  return (
    <PageContainer title={t('settings.gameRules.title', '游戏规则')}>
      <Card variant="borderless">
        {!settings ? (
          <Spin />
        ) : (
          <ProForm
            key={JSON.stringify(settings.values)}
            initialValues={settings.values}
            submitter={{
              searchConfig: { submitText: t('common.save', '保存') },
            }}
            onFinish={async (values) => {
              const changes = Object.fromEntries(
                Object.keys(settings.definitions)
                  .filter((key) => values[key] !== undefined)
                  .map((key) => [key, Number(values[key])])
              );
              await updateGameRuleSettings({
                changes,
                reason: String(values.reason),
              });
              const { data } = await getGameRuleSettings();
              setSettings(data);
              message.success(
                t('settings.gameRules.saved', '游戏规则设置已保存')
              );
              return true;
            }}
          >
            <Typography.Title level={5}>
              {t('settings.gameRules.section.experienceRates', '经验倍率')}
            </Typography.Title>
            {experienceRateKeys.map((key) => {
              const definition = settings.definitions[key];
              return (
                <ProFormDigit
                  key={key}
                  name={key}
                  label={ruleLabel(key, t)}
                  min={definition.minimum}
                  max={definition.maximum}
                  fieldProps={{ precision: 0 }}
                  addonAfter={t('settings.gameRules.unit.percent', '%')}
                  extra={ruleExtra(definition, t)}
                  width="md"
                  rules={[{ required: true }]}
                />
              );
            })}
            <Typography.Title level={5}>
              {t(
                'settings.gameRules.section.dropRates',
                '掉落倍率（普通魔物 & MVP）'
              )}
            </Typography.Title>
            <div
              style={{
                display: 'grid',
                gap: 32,
                gridTemplateColumns: 'repeat(2, minmax(280px, 1fr))',
                overflowX: 'auto',
              }}
            >
              {[
                {
                  key: 'normal',
                  keys: normalDropRateKeys,
                },
                {
                  key: 'mvp',
                  keys: mvpDropRateKeys,
                },
              ].map((group) => (
                <div key={group.key}>
                  {group.keys.map((key) => {
                    const definition = settings.definitions[key];
                    return (
                      <ProFormDigit
                        key={key}
                        name={key}
                        label={ruleLabel(key, t)}
                        min={definition.minimum}
                        max={definition.maximum}
                        fieldProps={{ precision: 0 }}
                        addonAfter={t('settings.gameRules.unit.percent', '%')}
                        extra={ruleExtra(definition, t)}
                        width="md"
                        rules={[{ required: true }]}
                      />
                    );
                  })}
                </div>
              ))}
            </div>
            <Typography.Title level={5}>
              {t('settings.gameRules.section.navigation', '网页地图传送')}
            </Typography.Title>
            <ProFormRadio.Group
              name="navigation_teleport_policy"
              label={ruleLabel('navigation_teleport_policy', t)}
              radioType="button"
              options={[
                {
                  label: t('settings.gameRules.policy.disabled', '关闭'),
                  value: 0,
                },
                {
                  label: t('settings.gameRules.policy.admin', '仅管理员'),
                  value: 1,
                },
                {
                  label: t('settings.gameRules.policy.everyone', '所有玩家'),
                  value: 2,
                },
              ]}
              rules={[{ required: true }]}
            />
            <ProFormRadio.Group
              name="navigation_teleport_cross_map"
              label={ruleLabel('navigation_teleport_cross_map', t)}
              radioType="button"
              options={[
                { label: t('common.enabled', '开启'), value: 1 },
                { label: t('common.disabled', '关闭'), value: 0 },
              ]}
              rules={[{ required: true }]}
            />
            <ProFormDigit
              name="navigation_teleport_cooldown"
              label={ruleLabel('navigation_teleport_cooldown', t)}
              min={settings.definitions.navigation_teleport_cooldown.minimum}
              max={settings.definitions.navigation_teleport_cooldown.maximum}
              fieldProps={{ precision: 0 }}
              addonAfter={t('settings.gameRules.unit.seconds', '秒')}
              extra={ruleExtra(
                settings.definitions.navigation_teleport_cooldown,
                t
              )}
              width="md"
              rules={[{ required: true }]}
            />
            <ProFormRadio.Group
              name="navigation_map_channels_enabled"
              label={ruleLabel('navigation_map_channels_enabled', t)}
              radioType="button"
              options={[
                { label: t('common.enabled', '开启'), value: 1 },
                { label: t('common.disabled', '关闭'), value: 0 },
              ]}
              extra={ruleExtra(
                settings.definitions.navigation_map_channels_enabled,
                t
              )}
              rules={[{ required: true }]}
            />
            <Typography.Title level={5}>
              {t('settings.gameRules.section.monsterSpawn', '游戏内魔物召唤')}
            </Typography.Title>
            <ProFormRadio.Group
              name="game_tools_monster_spawn_policy"
              label={ruleLabel('game_tools_monster_spawn_policy', t)}
              radioType="button"
              options={[
                {
                  label: t('settings.gameRules.policy.disabled', '关闭'),
                  value: 0,
                },
                {
                  label: t('settings.gameRules.policy.admin', '仅管理员'),
                  value: 1,
                },
                {
                  label: t('settings.gameRules.policy.everyone', '所有玩家'),
                  value: 2,
                },
              ]}
              rules={[{ required: true }]}
            />
            <ProFormDigit
              name="game_tools_monster_spawn_cooldown"
              label={ruleLabel('game_tools_monster_spawn_cooldown', t)}
              min={
                settings.definitions.game_tools_monster_spawn_cooldown.minimum
              }
              max={
                settings.definitions.game_tools_monster_spawn_cooldown.maximum
              }
              fieldProps={{ precision: 0 }}
              addonAfter={t('settings.gameRules.unit.seconds', '秒')}
              extra={ruleExtra(
                settings.definitions.game_tools_monster_spawn_cooldown,
                t
              )}
              width="md"
              rules={[{ required: true }]}
            />
            <ProFormDigit
              name="game_tools_monster_spawn_duration"
              label={ruleLabel('game_tools_monster_spawn_duration', t)}
              min={
                settings.definitions.game_tools_monster_spawn_duration.minimum
              }
              max={
                settings.definitions.game_tools_monster_spawn_duration.maximum
              }
              fieldProps={{ precision: 0 }}
              addonAfter={t('settings.gameRules.unit.seconds', '秒')}
              extra={ruleExtra(
                settings.definitions.game_tools_monster_spawn_duration,
                t
              )}
              width="md"
              rules={[{ required: true }]}
            />
            <ProFormRadio.Group
              name="game_tools_monster_spawn_allow_boss"
              label={ruleLabel('game_tools_monster_spawn_allow_boss', t)}
              radioType="button"
              options={[
                { label: t('common.enabled', '开启'), value: 1 },
                { label: t('common.disabled', '关闭'), value: 0 },
              ]}
              rules={[{ required: true }]}
            />
            <Typography.Title level={5}>
              {t('settings.gameRules.section.adventureTools', '冒险工具管理')}
            </Typography.Title>
            {[
              'game_tools_character_maintenance_policy',
              'game_tools_game_settings_policy',
              'game_tools_item_grant_policy',
            ].map((key) => (
              <ProFormRadio.Group
                key={key}
                name={key}
                label={ruleLabel(key, t)}
                radioType="button"
                options={[
                  {
                    label: t('settings.gameRules.policy.admin', '仅管理员'),
                    value: 1,
                  },
                  {
                    label: t('settings.gameRules.policy.everyone', '所有玩家'),
                    value: 2,
                  },
                ]}
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
