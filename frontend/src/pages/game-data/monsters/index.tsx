import { EyeOutlined } from '@ant-design/icons';
import {
  PageContainer,
  type ProColumns,
  ProTable,
} from '@ant-design/pro-components';
import { useAccess, useIntl } from '@umijs/max';
import { Button, Descriptions, Drawer, Empty, Space, Tag } from 'antd';
import { useMemo, useState } from 'react';
import MonsterSpawnModal from '@/components/MonsterSpawnModal';
import MonsterLocations from '@/components/MonsterLocations';
import {
  MONSTER_ELEMENTS,
  MONSTER_KINDS,
  MONSTER_RACES,
  MONSTER_SIZES,
} from '@/data/game/monster-types';
import {
  type GameDataMonster,
  type GameDataMonsterDrop,
  getMonster,
  listMonsters,
  monsterDropName,
  monsterName,
} from '@/services/game-data/monsters';

type Translate = (id: string, fallback: string) => string;
const typeLabel = (group: string, value: string, t: Translate) =>
  t(`gameData.monster.${group}.${value.toLowerCase()}`, value);

export default function Monsters() {
  const intl = useIntl();
  const access = useAccess();
  const [detail, setDetail] = useState<GameDataMonster>();
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });
  const locale = intl.locale;
  const columns = useMemo<ProColumns<GameDataMonster>[]>(
    () => [
      {
        title: t('gameData.monster.image', '形象'),
        dataIndex: 'image',
        search: false,
        width: 96,
        render: (_, row) => (
          <Button
            type="text"
            aria-label={`查看${monsterName(row, locale)}详情`}
            style={{ height: 'auto', padding: 0 }}
            onClick={async () => setDetail((await getMonster(row.Id)).data)}
          >
            <MonsterImage monster={row} locale={locale} />
          </Button>
        ),
      },
      {
        title: t('gameData.monster.id', '魔物 ID'),
        dataIndex: 'Id',
        search: false,
        width: 100,
      },
      {
        title: t('gameData.monster.name', '名称'),
        dataIndex: 'query',
        render: (_, row) => monsterName(row, locale),
      },
      { title: 'AegisName', dataIndex: 'AegisName', search: false },
      {
        title: t('gameData.monster.level', '等级'),
        dataIndex: 'Level',
        search: false,
        width: 80,
      },
      { title: 'HP', dataIndex: 'Hp', search: false, width: 100 },
      {
        title: t('gameData.monster.race', '种族'),
        dataIndex: 'race',
        valueEnum: Object.fromEntries(
          MONSTER_RACES.map((value) => [value, typeLabel('race', value, t)]),
        ),
        render: (_, row) => typeLabel('race', row.Race, t),
      },
      {
        title: t('gameData.monster.element', '属性'),
        dataIndex: 'element',
        valueEnum: Object.fromEntries(
          MONSTER_ELEMENTS.map((value) => [
            value,
            typeLabel('element', value, t),
          ]),
        ),
        render: (_, row) =>
          `${typeLabel('element', row.Element, t)} ${row.ElementLevel}`,
      },
      {
        title: t('gameData.monster.size', '体型'),
        dataIndex: 'size',
        hideInTable: true,
        valueEnum: Object.fromEntries(
          MONSTER_SIZES.map((value) => [value, typeLabel('size', value, t)]),
        ),
      },
      {
        title: t('gameData.monster.kind', '级别'),
        dataIndex: 'kind',
        hideInTable: true,
        valueType: 'select',
        initialValue: 'all',
        fieldProps: { allowClear: false },
        valueEnum: Object.fromEntries(
          MONSTER_KINDS.map((value) => [
            value,
            t(`gameData.monster.kind.${value}`, value),
          ]),
        ),
      },
      {
        title: t('common.actions', '操作'),
        valueType: 'option',
        width: 90,
        render: (_, row) => (
          <Space>
            {access.canGameControl && access.canViewPlayers && (
              <MonsterSpawnModal
                monsterId={row.Id}
                monsterName={monsterName(row, locale)}
              />
            )}
            <Button
              type="link"
              icon={<EyeOutlined />}
              onClick={async () => setDetail((await getMonster(row.Id)).data)}
            >
              {t('common.detail', '详情')}
            </Button>
          </Space>
        ),
      },
    ],
    [intl.locale, access.canGameControl, access.canViewPlayers],
  );
  return (
    <PageContainer title={t('gameData.monsters.title', '魔物图鉴')}>
      <ProTable<GameDataMonster>
        rowKey="Id"
        columns={columns}
        search={{ defaultCollapsed: false }}
        request={async (params) => ({
          ...(await listMonsters(params)),
          success: true,
        })}
      />
      <Drawer
        title={
          detail
            ? monsterName(detail, locale)
            : t('gameData.monster.detail', '魔物详情')
        }
        open={Boolean(detail)}
        onClose={() => setDetail(undefined)}
        size={560}
      >
        {detail && <MonsterDetails monster={detail} locale={locale} t={t} />}
      </Drawer>
    </PageContainer>
  );
}

function MonsterImage({
  monster,
  locale,
  detail = false,
}: {
  monster: GameDataMonster;
  locale: string;
  detail?: boolean;
}) {
  const frame = detail
    ? { width: 160, height: 140, imageWidth: 150, imageHeight: 128 }
    : { width: 72, height: 56, imageWidth: 68, imageHeight: 48 };
  return (
    <span
      style={{
        display: 'inline-flex',
        width: frame.width,
        height: frame.height,
        alignItems: 'center',
        justifyContent: 'center',
        overflow: 'hidden',
      }}
    >
      {monster.image ? (
        <img
          src={monster.image}
          alt={monsterName(monster, locale)}
          style={{
            display: 'block',
            width: 'auto',
            height: 'auto',
            maxWidth: frame.imageWidth,
            maxHeight: frame.imageHeight,
            objectFit: 'contain',
            imageRendering: 'pixelated',
          }}
        />
      ) : (
        <Empty
          image={Empty.PRESENTED_IMAGE_SIMPLE}
          description={false}
          styles={{ image: { height: 32, margin: 0 } }}
        />
      )}
    </span>
  );
}

function MonsterDetails({
  monster,
  locale,
  t,
}: {
  monster: GameDataMonster;
  locale: string;
  t: Translate;
}) {
  const stats = ['Str', 'Agi', 'Vit', 'Int', 'Dex', 'Luk'] as const;
  return (
    <Space orientation="vertical" size={16} style={{ width: '100%' }}>
      <div style={{ textAlign: 'center' }}>
        <MonsterImage monster={monster} locale={locale} detail />
      </div>
      <Descriptions bordered size="small" column={2}>
        <Descriptions.Item label={t('gameData.monster.id', '魔物 ID')}>
          {monster.Id}
        </Descriptions.Item>
        <Descriptions.Item label="AegisName">
          {monster.AegisName}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.monster.level', '等级')}>
          {monster.Level}
        </Descriptions.Item>
        <Descriptions.Item label="HP">
          {monster.Hp.toLocaleString()}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.monster.race', '种族')}>
          {typeLabel('race', monster.Race, t)}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.monster.element', '属性')}>
          {typeLabel('element', monster.Element, t)} {monster.ElementLevel}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.monster.size', '体型')}>
          {typeLabel('size', monster.Size, t)}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.monster.kind', '级别')}>
          <Tag color={monster.kind === 'normal' ? undefined : 'gold'}>
            {t(`gameData.monster.kind.${monster.kind}`, monster.kind)}
          </Tag>
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.monster.baseExp', '基础经验')}>
          {monster.BaseExp ?? 0}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.monster.jobExp', '职业经验')}>
          {monster.JobExp ?? 0}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.monster.attack', '攻击')}>
          {monster.Attack ?? 0} - {monster.Attack2 ?? monster.Attack ?? 0}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.monster.defense', '防御')}>
          {monster.Defense ?? 0} / {monster.MagicDefense ?? 0}
        </Descriptions.Item>
        {stats.map((stat) => (
          <Descriptions.Item key={stat} label={stat}>
            {monster[stat] ?? 1}
          </Descriptions.Item>
        ))}
        <Descriptions.Item
          span={2}
          label={t('gameData.monster.drops', '掉落物品')}
        >
          <DropList drops={monster.Drops} locale={locale} t={t} />
        </Descriptions.Item>
        {monster.MvpDrops && (
          <Descriptions.Item
            span={2}
            label={t('gameData.monster.mvpDrops', 'MVP 奖励')}
          >
            <DropList drops={monster.MvpDrops} locale={locale} t={t} />
          </Descriptions.Item>
        )}
      </Descriptions>
      <MonsterLocations monsterId={monster.Id} />
    </Space>
  );
}

function DropList({
  drops,
  locale,
  t,
}: {
  drops?: GameDataMonsterDrop[];
  locale: string;
  t: Translate;
}) {
  return drops?.length ? (
    <Space wrap>
      {drops.map((drop) => (
        <Tag key={`${drop.Item}-${drop.Rate}`}>
          {monsterDropName(drop, locale)} · {(drop.Rate / 100).toFixed(2)}%
        </Tag>
      ))}
    </Space>
  ) : (
    t('gameData.monster.noDrops', '无掉落资料')
  );
}
