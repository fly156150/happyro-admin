import { EyeOutlined } from '@ant-design/icons';
import {
  PageContainer,
  type ProColumns,
  type ProFormInstance,
  ProTable,
} from '@ant-design/pro-components';
import { useAccess, useIntl } from '@umijs/max';
import { Button, Descriptions, Drawer, Tag } from 'antd';
import { useMemo, useRef, useState } from 'react';
import ItemGrantModal from '@/components/ItemGrantModal';
import { ITEM_TYPE_CODES, WEAPON_SUBTYPE_CODES } from '@/data/game/item-types';
import {
  type GameDataItem,
  getItem,
  type ItemQuery,
  itemName,
  listItems,
} from '@/services/game-data/items';

type Translate = (id: string, fallback: string) => string;

function itemTypeLabel(type: string | undefined, t: Translate): string {
  return type ? t(`gameData.item.type.${type.toLowerCase()}`, type) : '-';
}

function weaponSubtypeLabel(
  subtype: string | number | undefined,
  t: Translate,
): string {
  return subtype
    ? t(
        `gameData.item.weaponSubtype.${String(subtype).toLowerCase()}`,
        String(subtype),
      )
    : '-';
}

function sourceLabel(source: GameDataItem['source'], t: Translate): string {
  return t(`gameData.item.source.${source}`, source);
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

  const columns = useMemo<ProColumns<GameDataItem>[]>(
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
      ...(selectedItemType === 'Weapon'
        ? [
            {
              title: t('gameData.item.weaponSubtype', '武器类型'),
              dataIndex: 'subtype',
              hideInTable: true,
              valueEnum: Object.fromEntries(
                WEAPON_SUBTYPE_CODES.map((subtype) => [
                  subtype,
                  weaponSubtypeLabel(subtype, t),
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
            {access.canGrantItems && (
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
    [access.canGrantItems, intl.locale, selectedItemType],
  );

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
              if (changedValues.type !== 'Weapon') {
                formRef.current?.setFieldValue('subtype', undefined);
              }
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
          <Descriptions.Item
            label={t('gameData.item.weaponSubtype', '武器类型')}
          >
            <Tag>{weaponSubtypeLabel(item.SubType, t)}</Tag>
          </Descriptions.Item>
        )}
        <Descriptions.Item label={t('gameData.item.source', '数据来源')}>
          <Tag>{sourceLabel(item.source, t)}</Tag>
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.item.buy', '买入价')}>
          {item.Buy ?? '-'}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.item.sell', '卖出价')}>
          {item.Sell ?? '-'}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.item.description', '说明')}>
          {item.description?.length
            ? item.description.map((line) => <div key={line}>{line}</div>)
            : t('gameData.item.noDescription', '暂无说明')}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.item.script', '服务器脚本')}>
          {item.Script || '-'}
        </Descriptions.Item>
      </Descriptions>
    </>
  );
}
