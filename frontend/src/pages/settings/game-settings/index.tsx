import {
  PageContainer,
  ProForm,
  ProFormDigit,
  ProFormRadio,
  ProFormText,
} from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { App, Card, Flex, Spin, Tabs, Typography } from 'antd';
import { useEffect, useState, type CSSProperties, type ReactNode } from 'react';
import {
  type GameSettingDefinition,
  type GameSettings,
  getGameSettings,
  updateGameSettings,
} from '@/services/settings/game-settings';

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

function ruleLabel(key: string, t: Translate): string {
  return t(`settings.gameSettings.key.${key}`, key);
}

function isRate(definition: GameSettingDefinition): boolean {
  return definition.unit === 'percent';
}

function toDisplayValue(value: number, definition: GameSettingDefinition): number {
  return isRate(definition) ? value / 100 : value;
}

function toStoredValue(value: number, definition: GameSettingDefinition): number {
  return isRate(definition) ? Math.round(value * 100) : value;
}

function ruleExtra(definition: GameSettingDefinition, t: Translate) {
  return (
    <Flex gap={8} wrap>
      <Typography.Text type="secondary">
        {t('settings.gameSettings.source', '来源')}: {definition.source}
      </Typography.Text>
      <Typography.Text type="secondary">
        {toDisplayValue(definition.minimum, definition)} -{' '}
        {toDisplayValue(definition.maximum, definition)}
      </Typography.Text>
    </Flex>
  );
}

function twoColumnStyle(): CSSProperties {
  return {
    display: 'grid',
    gap: 32,
    gridTemplateColumns: 'repeat(2, minmax(280px, 1fr))',
    overflowX: 'auto',
  };
}

export default function GameSettingsPage() {
  const intl = useIntl();
  const { message } = App.useApp();
  const [settings, setSettings] = useState<GameSettings>();
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });

  useEffect(() => {
    getGameSettings()
      .then(({ data }) => setSettings(data))
      .catch(() =>
        message.error(t('settings.gameSettings.loadFailed', '游戏设置加载失败'))
      );
  }, []);

  const initialValues = settings
    ? Object.fromEntries(
        Object.entries(settings.values).map(([key, value]) => [
          key,
          settings.definitions[key]
            ? toDisplayValue(value, settings.definitions[key])
            : value,
        ])
      )
    : undefined;

  return (
    <PageContainer title={t('settings.gameSettings.title', '游戏设置')}>
      <Card variant="borderless">
        {!settings || !initialValues ? (
          <Spin />
        ) : (
          <ProForm
            key={JSON.stringify(settings.values)}
            initialValues={initialValues}
            submitter={{
              searchConfig: { submitText: t('common.save', '保存') },
            }}
            onFinish={async (values) => {
              const changes = Object.fromEntries(
                Object.keys(settings.definitions)
                  .filter((key) => values[key] !== undefined)
                  .map((key) => [
                    key,
                    toStoredValue(Number(values[key]), settings.definitions[key]),
                  ])
              );
              const remark = String(values.remark ?? '').trim();
              await updateGameSettings({
                changes,
                ...(remark ? { remark } : {}),
              });
              const { data } = await getGameSettings();
              setSettings(data);
              message.success(t('settings.gameSettings.saved', '游戏设置已保存'));
              return true;
            }}
          >
            <Tabs
              defaultActiveKey="experience"
              items={[
                {
                  key: 'experience',
                  label: t('settings.gameSettings.tab.experience', '经验倍率'),
                  children: <ExperienceRateSettings settings={settings} t={t} />,
                },
                {
                  key: 'drops',
                  label: t('settings.gameSettings.tab.drops', '掉落倍率'),
                  children: <DropRateSettings settings={settings} t={t} />,
                },
                {
                  key: 'navigation',
                  label: t('settings.gameSettings.tab.navigation', '地图传送'),
                  children: <NavigationSettings settings={settings} t={t} />,
                },
                {
                  key: 'monster',
                  label: t('settings.gameSettings.tab.monster', '魔物召唤'),
                  children: <MonsterSpawnSettings settings={settings} t={t} />,
                },
                {
                  key: 'adventure',
                  label: t('settings.gameSettings.tab.adventure', '冒险工具'),
                  children: <AdventureToolSettings t={t} />,
                },
              ]}
            />
            <ProFormText
              name="remark"
              label={t('settings.gameSettings.remark', '修改备注')}
              width="md"
            />
          </ProForm>
        )}
      </Card>
    </PageContainer>
  );
}

function SectionTitle({ children }: { children: ReactNode }) {
  return <Typography.Title level={5}>{children}</Typography.Title>;
}

function ExperienceRateSettings({
  settings,
  t,
}: {
  settings: GameSettings;
  t: Translate;
}) {
  return (
    <>
      <SectionTitle>
        {t('settings.gameSettings.tab.experience', '经验倍率')}
      </SectionTitle>
      <div style={twoColumnStyle()}>
        <RateField
          name="base_exp_rate"
          definition={settings.definitions.base_exp_rate}
          t={t}
        />
        <RateField
          name="job_exp_rate"
          definition={settings.definitions.job_exp_rate}
          t={t}
        />
      </div>
    </>
  );
}

function DropRateSettings({
  settings,
  t,
}: {
  settings: GameSettings;
  t: Translate;
}) {
  return (
    <>
      <SectionTitle>{t('settings.gameSettings.tab.drops', '掉落倍率')}</SectionTitle>
      <div style={twoColumnStyle()}>
        {[normalDropRateKeys, mvpDropRateKeys].map((keys, index) => (
          <div key={index}>
            {keys.map((key) => (
              <RateField
                key={key}
                name={key}
                definition={settings.definitions[key]}
                t={t}
              />
            ))}
          </div>
        ))}
      </div>
    </>
  );
}

function RateField({
  name,
  definition,
  t,
}: {
  name: string;
  definition: GameSettingDefinition;
  t: Translate;
}) {
  return (
    <ProFormDigit
      name={name}
      label={ruleLabel(name, t)}
      min={toDisplayValue(definition.minimum, definition)}
      max={toDisplayValue(definition.maximum, definition)}
      fieldProps={{ precision: 2, step: 0.01, min: 0 }}
      extra={ruleExtra(definition, t)}
      width="md"
      rules={[{ required: true }]}
    />
  );
}

function PolicyOptions({ t }: { t: Translate }) {
  return [
    { label: t('settings.gameSettings.policy.disabled', '关闭'), value: 0 },
    { label: t('settings.gameSettings.policy.admin', '仅管理员'), value: 1 },
    { label: t('settings.gameSettings.policy.everyone', '所有玩家'), value: 2 },
  ];
}

function BinaryOptions({ t }: { t: Translate }) {
  return [
    { label: t('common.enabled', '开启'), value: 1 },
    { label: t('common.disabled', '关闭'), value: 0 },
  ];
}

function NavigationSettings({
  settings,
  t,
}: {
  settings: GameSettings;
  t: Translate;
}) {
  return (
    <>
      <SectionTitle>
        {t('settings.gameSettings.tab.navigation', '地图传送')}
      </SectionTitle>
      <ProFormRadio.Group
        name="navigation_teleport_policy"
        label={ruleLabel('navigation_teleport_policy', t)}
        radioType="button"
        options={PolicyOptions({ t })}
        rules={[{ required: true }]}
      />
      <ProFormRadio.Group
        name="navigation_teleport_cross_map"
        label={ruleLabel('navigation_teleport_cross_map', t)}
        radioType="button"
        options={BinaryOptions({ t })}
        rules={[{ required: true }]}
      />
      <ProFormDigit
        name="navigation_teleport_cooldown"
        label={ruleLabel('navigation_teleport_cooldown', t)}
        min={settings.definitions.navigation_teleport_cooldown.minimum}
        max={settings.definitions.navigation_teleport_cooldown.maximum}
        fieldProps={{ precision: 0 }}
        addonAfter={t('settings.gameSettings.unit.seconds', '秒')}
        extra={ruleExtra(settings.definitions.navigation_teleport_cooldown, t)}
        width="md"
        rules={[{ required: true }]}
      />
      <ProFormRadio.Group
        name="navigation_map_channels_enabled"
        label={ruleLabel('navigation_map_channels_enabled', t)}
        radioType="button"
        options={BinaryOptions({ t })}
        extra={ruleExtra(settings.definitions.navigation_map_channels_enabled, t)}
        rules={[{ required: true }]}
      />
    </>
  );
}

function MonsterSpawnSettings({
  settings,
  t,
}: {
  settings: GameSettings;
  t: Translate;
}) {
  return (
    <>
      <SectionTitle>
        {t('settings.gameSettings.tab.monster', '魔物召唤')}
      </SectionTitle>
      <ProFormRadio.Group
        name="game_tools_monster_spawn_policy"
        label={ruleLabel('game_tools_monster_spawn_policy', t)}
        radioType="button"
        options={PolicyOptions({ t })}
        rules={[{ required: true }]}
      />
      {(
        [
          'game_tools_monster_spawn_cooldown',
          'game_tools_monster_spawn_duration',
        ] as const
      ).map((key) => (
        <ProFormDigit
          key={key}
          name={key}
          label={ruleLabel(key, t)}
          min={settings.definitions[key].minimum}
          max={settings.definitions[key].maximum}
          fieldProps={{ precision: 0 }}
          addonAfter={t('settings.gameSettings.unit.seconds', '秒')}
          extra={ruleExtra(settings.definitions[key], t)}
          width="md"
          rules={[{ required: true }]}
        />
      ))}
      <ProFormRadio.Group
        name="game_tools_monster_spawn_allow_boss"
        label={ruleLabel('game_tools_monster_spawn_allow_boss', t)}
        radioType="button"
        options={BinaryOptions({ t })}
        rules={[{ required: true }]}
      />
    </>
  );
}

function AdventureToolSettings({ t }: { t: Translate }) {
  return (
    <>
      <SectionTitle>
        {t('settings.gameSettings.tab.adventure', '冒险工具')}
      </SectionTitle>
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
          options={PolicyOptions({ t }).slice(1)}
          rules={[{ required: true }]}
        />
      ))}
    </>
  );
}
