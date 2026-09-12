import { EyeOutlined } from '@ant-design/icons';
import {
  PageContainer,
  type ProColumns,
  type ProFormInstance,
  ProTable,
} from '@ant-design/pro-components';
import { useAccess, useIntl } from '@umijs/max';
import { Button, Descriptions, Drawer, Tag } from 'antd';
import type { RefObject } from 'react';
import { useMemo, useRef, useState } from 'react';
import ItemGrantModal from '@/components/ItemGrantModal';
import {
  ARMOR_SLOT_CODES,
  CARD_SUBTYPE_CODES,
  ITEM_TYPE_CODES,
  SUBTYPE_FILTER_TYPES,
  WEAPON_SUBTYPE_CODES,
} from '@/data/game/item-types';
import {
  type GameDataItem,
  getItem,
  type ItemQuery,
  itemName,
  listItems,
} from '@/services/game-data/items';
import { toPlainRagnarokText } from '@/utils/format';

type Translate = (id: string, fallback: string) => string;

type ItemColumnsOptions = {
  canGrantItems: boolean;
  formRef: RefObject<ProFormInstance | undefined>;
  locale: string;
  selectedItemType?: string;
  setDetail: (item: GameDataItem) => void;
  t: Translate;
};

function itemTypeLabel(type: string | undefined, t: Translate): string {
  return type ? t(`gameData.item.type.${type.toLowerCase()}`, type) : '-';
}

function hasSubtypeFilter(type: string | undefined): boolean {
  return SUBTYPE_FILTER_TYPES.includes(
    type as (typeof SUBTYPE_FILTER_TYPES)[number],
  );
}

function subtypeCodes(type: string | undefined): readonly string[] {
  if (type === 'Weapon') {
    return WEAPON_SUBTYPE_CODES;
  }
  if (type === 'Armor') {
    return ARMOR_SLOT_CODES;
  }
  if (type === 'Card') {
    return CARD_SUBTYPE_CODES;
  }
  return [];
}

function subtypeMessageId(type: string | undefined, subtype: string): string {
  const key = subtype.toLowerCase();
  if (type === 'Weapon') {
    return `gameData.item.weaponSubtype.${key}`;
  }
  if (type === 'Ammo') {
    return `gameData.item.ammoSubtype.${key}`;
  }
  if (type === 'Card' && key === 'enchant') {
    return 'gameData.item.cardSubtype.enchant';
  }
  return `gameData.item.equipSlot.${key}`;
}

function subtypeLabel(
  subtype: string | number | undefined,
  type: string | undefined,
  t: Translate,
): string {
  if (!subtype) {
    return '-';
  }
  const value = String(subtype);
  return t(subtypeMessageId(type, value), value);
}

function sourceLabel(source: GameDataItem['source'], t: Translate): string {
  return t(`gameData.item.source.${source}`, source);
}

function useItemColumns({
  canGrantItems,
  formRef,
  locale,
  selectedItemType,
  setDetail,
  t,
}: ItemColumnsOptions) {
  return useMemo<ProColumns<GameDataItem>[]>(
    () => [
      {
        title: t('gameData.item.icon', '图标'),
        dataIndex: 'illustration',
        search: false,
        width: 72,
        render: (_, row) => (
          <span
            style={{
              display: 'inline-flex',
              width: 48,
              height: 64,
              alignItems: 'center',
              justifyContent: 'center',
              background: '#fff',
              overflow: 'hidden',
            }}
          >
            <img
              src={row.illustration || row.icon}
              alt={itemName(row, locale)}
              width={48}
              height={64}
              style={{ objectFit: 'contain', display: 'block' }}
            />
          </span>
        ),
      },
      {
        title: t('gameData.item.id', '物品 ID'),
        dataIndex: 'Id',
        search: false,
        width: 100,
      },
      {
        title: t('gameData.item.name', '名称'),
        dataIndex: 'query',
        render: (_, row) => itemName(row, locale),
      },
      {
        title: t('gameData.item.aegisName', 'AegisName'),
        dataIndex: 'AegisName',
        search: false,
      },
      {
        title: t('gameData.item.type', '类型'),
        dataIndex: 'type',
        valueEnum: Object.fromEntries(
          ITEM_TYPE_CODES.map((type) => [type, itemTypeLabel(type, t)]),
        ),
        render: (_, row) => itemTypeLabel(row.Type, t),
      },
      ...(hasSubtypeFilter(selectedItemType)
        ? [
            {
              title: t('gameData.item.subtype', '子类'),
              dataIndex: 'subtype',
              hideInTable: true,
              valueEnum: Object.fromEntries(
                subtypeCodes(selectedItemType).map((subtype) => [
                  subtype,
                  subtypeLabel(subtype, selectedItemType, t),
                ]),
              ),
            },
          ]
        : []),
      {
        title: t('gameData.item.weight', '重量'),
        dataIndex: 'Weight',
        search: false,
      },
      {
        title: t('gameData.item.price', '价格'),
        dataIndex: 'Buy',
        search: false,
        render: (_, row) => `买 ${row.Buy ?? '-'} / 卖 ${row.Sell ?? '-'}`,
      },
      {
        title: t('gameData.item.slots', '洞数'),
        dataIndex: 'Slots',
        search: false,
        render: (_, row) => row.Slots ?? 0,
      },
      {
        title: t('gameData.item.range', '数据范围'),
        dataIndex: 'range',
        hideInTable: true,
        initialValue: 'client',
        valueEnum: {
          client: t('gameData.item.range.client', '仅客户端'),
          server: t('gameData.item.range.server', '仅服务端'),
          all: t('gameData.item.range.all', '全部'),
        },
      },
      {
        title: t('common.actions', '操作'),
        valueType: 'option',
        render: (_, row) => (
          <>
            {canGrantItems && (
              <ItemGrantModal
                itemId={row.Id}
                itemName={itemName(row, locale)}
              />
            )}
            <Button
              type="link"
              icon={<EyeOutlined />}
              onClick={async () => {
                const query = formRef.current?.getFieldsValue() as ItemQuery;
                setDetail((await getItem(row.Id, query)).data);
              }}
            >
              {t('common.detail', '详情')}
            </Button>
          </>
        ),
      },
    ],
    [canGrantItems, locale, selectedItemType],
  );
}

export default function Items() {
  const intl = useIntl();
  const access = useAccess();
  const formRef = useRef<ProFormInstance | undefined>(undefined);
  const [detail, setDetail] = useState<GameDataItem>();
  const [selectedItemType, setSelectedItemType] = useState<string>();
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });
  const locale = intl.locale;

  const columns = useItemColumns({
    canGrantItems: access.canGrantItems,
    formRef,
    locale,
    selectedItemType,
    setDetail,
    t,
  });

  return (
    <PageContainer title={t('gameData.items.title', '物品图鉴')}>
      <ProTable<GameDataItem>
        formRef={formRef}
        rowKey="Id"
        columns={columns}
        search={{ defaultCollapsed: false }}
        form={{
          onValuesChange: (changedValues) => {
            if ('type' in changedValues) {
              setSelectedItemType(changedValues.type);
              formRef.current?.setFieldValue('subtype', undefined);
            }
          },
        }}
        request={async (params) => {
          const result = await listItems(params);
          return { data: result.data, total: result.total, success: true };
        }}
      />
      <Drawer
        title={
          detail
            ? itemName(detail, locale)
            : t('gameData.item.detail', '物品详情')
        }
        open={Boolean(detail)}
        onClose={() => setDetail(undefined)}
        size={520}
      >
        {detail && <ItemDetails item={detail} locale={locale} t={t} />}
      </Drawer>
    </PageContainer>
  );
}

function ItemDetails({
  item,
  locale,
  t,
}: {
  item: GameDataItem;
  locale: string;
  t: Translate;
}) {
  return (
    <>
      <img
        src={item.illustration || item.icon}
        alt={itemName(item, locale)}
        width={75}
        height={100}
        style={{
          objectFit: 'contain',
          display: 'block',
          margin: '0 auto 16px',
        }}
      />
      <Descriptions column={1} bordered size="small">
        <Descriptions.Item label={t('gameData.item.id', '物品 ID')}>
          {item.Id}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.item.name', '名称')}>
          {itemName(item, locale)}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.item.aegisName', 'AegisName')}>
          {item.AegisName || '-'}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.item.type', '类型')}>
          <Tag>{itemTypeLabel(item.Type, t)}</Tag>
        </Descriptions.Item>
        {item.SubType && (
          <Descriptions.Item label={t('gameData.item.subtype', '子类')}>
            <Tag>{subtypeLabel(item.SubType, item.Type, t)}</Tag>
          </Descriptions.Item>
        )}
        <Descriptions.Item label={t('gameData.item.source', '数据来源')}>
          <Tag>{sourceLabel(item.source, t)}</Tag>
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.item.price', '价格')}>
          买 {item.Buy ?? '-'} / 卖 {item.Sell ?? '-'}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.item.slots', '洞数')}>
          {item.Slots ?? 0}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.item.description', '说明')}>
          {item.description?.length
            ? item.description.map((line) => (
                <div key={line}>{toPlainRagnarokText(line)}</div>
              ))
            : t('gameData.item.noDescription', '暂无说明')}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.item.script', '服务器脚本')}>
          {item.Script || '-'}
        </Descriptions.Item>
      </Descriptions>
    </>
  );
}
