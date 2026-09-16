import {
  PageContainer,
  ProForm,
  ProFormDigit,
  type ProFormInstance,
  ProFormRadio,
} from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { App, Card, Spin, Tabs } from 'antd';
import { useEffect, useRef, useState } from 'react';
import {
  type GameSettingDefinition,
  type GameSettings,
  getGameSettings,
  updateGameSettings,
} from '@/services/settings/game-settings';

type Translate = (
  id: string,
  fallback: string,
  values?: Record<string, string | number>,
) => string;

const normalDropRateKeys = [
  'item_rate_common',
  'item_rate_heal',
  'item_rate_use',
  'item_rate_equip',
  'item_rate_card',
] as const;
const dropCategories = [
  { suffix: '', key: 'normal', label: '普通魔物' },
  { suffix: '_boss', key: 'mini', label: 'Mini' },
  { suffix: '_mvp', key: 'mvp', label: 'MVP' },
] as const;

function ruleLabel(key: string, t: Translate): string {
  return t(`settings.gameSettings.key.${key}`, key);
}

function isRate(definition: GameSettingDefinition): boolean {
  return definition.unit === 'percent';
}

function toDisplayValue(
  value: number,
  definition: GameSettingDefinition,
): number {
  return isRate(definition) ? value / 100 : value;
}

function toStoredValue(
  value: number,
  definition: GameSettingDefinition,
): number {
  return isRate(definition) ? Math.round(value * 100) : value;
}

function formatDisplayNumber(value: number | string | undefined): string {
  if (value === undefined || value === null || value === '') {
    return '';
  }
  const numeric = Number(value);
  if (!Number.isFinite(numeric)) {
    return '';
  }
  return String(Number(numeric.toFixed(2)));
}

function displayValues(settings: GameSettings): Record<string, number> {
  return Object.fromEntries(
    Object.entries(settings.values).map(([key, value]) => [
      key,
      settings.definitions[key]
        ? toDisplayValue(value, settings.definitions[key])
        : value,
    ]),
  );
}

function rangePlaceholder(definition: GameSettingDefinition): string {
  const minimum = formatDisplayNumber(toDisplayValue(definition.minimum, definition));
  const maximum = formatDisplayNumber(toDisplayValue(definition.maximum, definition));
  return `${minimum} - ${maximum}`;
}

export default function GameSettingsPage() {
  const intl = useIntl();
  const { message } = App.useApp();
  const [settings, setSettings] = useState<GameSettings>();
  const [activeTab, setActiveTab] = useState('experience');
  const formRef = useRef<ProFormInstance>(undefined);
  const t: Translate = (id, fallback, values) =>
    intl.formatMessage({ id, defaultMessage: fallback }, values);

  useEffect(() => {
    getGameSettings()
      .then(({ data }) => setSettings(data))
      .catch(() =>
        message.error(
          t('settings.gameSettings.loadFailed', '游戏设置加载失败'),
        ),
      );
  }, []);

  const initialValues = settings ? displayValues(settings) : undefined;

  return (
    <PageContainer title={t('settings.gameSettings.title', '游戏设置')}>
      <Card variant="borderless">
        {!settings || !initialValues ? (
          <Spin />
        ) : (
          <ProForm
            formRef={formRef}
            initialValues={initialValues}
            submitter={{
              resetButtonProps: false,
              searchConfig: { submitText: t('common.save', '保存') },
            }}
            onFinish={async (values) => {
              const changes = Object.fromEntries(
                Object.keys(settings.definitions)
                  .filter((key) => values[key] !== undefined)
                  .map((key) => [
                    key,
                    toStoredValue(
                      Number(values[key]),
                      settings.definitions[key],
                    ),
                  ]),
              );
              await updateGameSettings({
                changes,
              });
              const { data } = await getGameSettings();
              setSettings(data);
              formRef.current?.setFieldsValue(displayValues(data));
              message.success(
                t('settings.gameSettings.saved', '游戏设置已保存'),
              );
              return true;
            }}
          >
            <Tabs
              activeKey={activeTab}
              onChange={setActiveTab}
              items={[
                {
                  key: 'experience',
                  label: t('settings.gameSettings.tab.experience', '经验倍率'),
                  children: (
                    <ExperienceRateSettings settings={settings} t={t} />
                  ),
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

          </ProForm>
        )}
      </Card>
    </PageContainer>
  );
}

function ExperienceRateSettings({
  settings,
  t,
}: {
  settings: GameSettings;
  t: Translate;
}) {
  return (
    <div>
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
    <div style={{ overflowX: 'auto' }}>
      <table style={{ width: '100%', minWidth: 600, borderCollapse: 'collapse' }}>
        <thead>
          <tr>
            <th scope="col" style={{ textAlign: 'left', padding: 8 }}>
              {t('settings.gameSettings.dropType', '物品类型')}
            </th>
            {dropCategories.map((category) => (
              <th key={category.key} scope="col" style={{ textAlign: 'left', padding: 8 }}>
                {t(`settings.gameSettings.dropCategory.${category.key}`, category.label)}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {normalDropRateKeys.map((baseKey) => (
            <tr key={baseKey}>
              <th scope="row" style={{ textAlign: 'left', padding: 8, fontWeight: 'normal' }}>
                {ruleLabel(baseKey, t)}
              </th>
              {dropCategories.map(({ suffix }) => {
                const key = `${baseKey}${suffix}`;
                return (
                  <td key={key} style={{ padding: 8 }}>
                    <RateField name={key} definition={settings.definitions[key]} t={t} compact />
                  </td>
                );
              })}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function RateField({
  name,
  definition,
  t,
  compact = false,
}: {
  name: string;
  definition: GameSettingDefinition;
  t: Translate;
  compact?: boolean;
}) {
  const minimum = toDisplayValue(definition.minimum, definition);
  const maximum = toDisplayValue(definition.maximum, definition);
  return (
    <ProFormDigit
      name={name}
      label={compact ? undefined : ruleLabel(name, t)}
      formItemProps={compact ? { style: { marginBottom: 0 } } : undefined}
      fieldProps={{
        'aria-label': ruleLabel(name, t),
        placeholder: rangePlaceholder(definition),
        style: compact ? { width: '100%' } : undefined,
        step: 0.01,
        formatter: (value, info) =>
          info.userTyping ? info.input : formatDisplayNumber(value),

      }}
      addonAfter={t('settings.gameSettings.unit.times', '倍')}
      width={compact ? undefined : 'md'}
      rules={rangeRules(minimum, maximum, t)}
    />
  );
}

function rangeRules(minimum: number, maximum: number, t: Translate) {
  return [
    { required: true },
    {
      validator: async (_: unknown, value: unknown) => {
        const numeric = Number(value);
        if (
          !Number.isFinite(numeric) ||
          numeric < minimum ||
          numeric > maximum
        ) {
          throw new Error(
            t(
              'settings.gameSettings.range',
              '请输入 {min} 到 {max} 之间的数值',
              {
                min: formatDisplayNumber(minimum),
                max: formatDisplayNumber(maximum),
              },
            ),
          );
        }
      },
    },
  ];
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
        fieldProps={{ precision: 0, placeholder: rangePlaceholder(settings.definitions.navigation_teleport_cooldown) }}
        addonAfter={t('settings.gameSettings.unit.seconds', '秒')}
        width="md"
        rules={rangeRules(
          settings.definitions.navigation_teleport_cooldown.minimum,
          settings.definitions.navigation_teleport_cooldown.maximum,
          t,
        )}
      />
      <ProFormRadio.Group
        name="navigation_map_channels_enabled"
        label={ruleLabel('navigation_map_channels_enabled', t)}
        radioType="button"
        options={BinaryOptions({ t })}
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
          fieldProps={{ precision: 0, placeholder: rangePlaceholder(settings.definitions[key]) }}
          addonAfter={t('settings.gameSettings.unit.seconds', '秒')}
          width="md"
          rules={rangeRules(
            settings.definitions[key].minimum,
            settings.definitions[key].maximum,
            t,
          )}
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
