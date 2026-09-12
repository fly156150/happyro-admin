import { EyeOutlined } from '@ant-design/icons';
import {
  type ActionType,
  PageContainer,
  type ProColumns,
  ProTable,
} from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { Button, Descriptions, Drawer, Empty, Image, Space } from 'antd';
import { useEffect, useRef, useState } from 'react';
import {
  type GameDataNpc,
  npcsOnMap,
} from '@/pages/game-data/npcs/npcCatalog';
import { listNpcs } from '@/pages/game-data/npcs/service';
import { listMaps } from './service';

type MapRow = {
  id: number | null;
  map: string;
  name_zh_cn?: string;
  image?: string;
};

function MapPreview({ row }: { row: MapRow }) {
  const intl = useIntl();
  const [failed, setFailed] = useState(false);
  if (row.image && !failed) {
    return (
      <img
        src={row.image}
        alt={row.name_zh_cn || row.map}
        onError={() => setFailed(true)}
        style={{ width: 96, height: 64, objectFit: 'cover', borderRadius: 4 }}
      />
    );
  }
  return (
    <div
      style={{
        width: 96,
        height: 64,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        color: '#8c8c8c',
        border: '1px solid #c9d7d1',
        borderRadius: 4,
        backgroundColor: '#fafafa',
        textAlign: 'center',
      }}
    >
      <span style={{ fontSize: 12, lineHeight: 1.5 }}>
        {intl.formatMessage({
          id: 'gameData.item.noImage',
          defaultMessage: '暂无图片',
        })}
      </span>
    </div>
  );
}

export default function Maps() {
  const intl = useIntl();
  const [rows, setRows] = useState<MapRow[]>([]);
  const [npcs, setNpcs] = useState<GameDataNpc[]>([]);
  const [detail, setDetail] = useState<MapRow>();
  const actionRef = useRef<ActionType>(null);
  useEffect(() => {
    listMaps().then((result) => {
      setRows(result.data);
      actionRef.current?.reload();
    });
    listNpcs().then((result) => {
      setNpcs(result.data);
    });
  }, []);
  const columns: ProColumns<MapRow>[] = [
    {
      title: intl.formatMessage({
        id: 'gameData.map.image',
        defaultMessage: '地图',
      }),
      dataIndex: 'image',
      search: false,
      width: 120,
      render: (_, row) => <MapPreview row={row} />,
    },
    {
      title: intl.formatMessage({
        id: 'gameData.map.id',
        defaultMessage: '地图 ID',
      }),
      dataIndex: 'id',
      search: false,
    },
    {
      title: intl.formatMessage({
        id: 'gameData.map.nameZhCn',
        defaultMessage: '中文名称',
      }),
      dataIndex: 'name_zh_cn',
      render: (_, row) =>
        row.name_zh_cn ||
        intl.formatMessage({
          id: 'common.notAvailable',
          defaultMessage: '暂无',
        }),
    },
    {
      title: intl.formatMessage({
        id: 'gameData.map.name',
        defaultMessage: '地图代码',
      }),
      dataIndex: 'map',
    },
    {
      title: intl.formatMessage({ id: 'common.actions', defaultMessage: '操作' }),
      valueType: 'option',
      width: 90,
      search: false,
      render: (_, row) => (
        <Button type="link" icon={<EyeOutlined />} onClick={() => setDetail(row)}>
          {intl.formatMessage({ id: 'common.detail', defaultMessage: '详情' })}
        </Button>
      ),
    },
  ];
  return (
    <PageContainer
      title={intl.formatMessage({
        id: 'menu.gameData.maps',
        defaultMessage: '地图查询',
      })}
    >
      <ProTable<MapRow>
        rowKey="map"
        columns={columns}
        actionRef={actionRef}
        request={async (params) => {
          const code = String(params.map ?? '').toLowerCase();
          const name = String(params.name_zh_cn ?? '').toLowerCase();
          const data = rows.filter(
            (row) =>
              (!code || row.map.toLowerCase().includes(code)) &&
              (!name || row.name_zh_cn?.toLowerCase().includes(name)),
          );
          return { data, total: data.length, success: true };
        }}
        pagination={{ pageSize: 20 }}
      />
      <Drawer
        title={
          detail?.name_zh_cn ||
          detail?.map ||
          intl.formatMessage({ id: 'gameData.map.detail', defaultMessage: '地图详情' })
        }
        open={Boolean(detail)}
        onClose={() => setDetail(undefined)}
        size={560}
      >
        {detail ? <MapDetails map={detail} npcs={npcsOnMap(npcs, detail.map)} /> : null}
      </Drawer>
    </PageContainer>
  );
}

function MapDetails({ map, npcs }: { map: MapRow; npcs: GameDataNpc[] }) {
  const intl = useIntl();
  return (
    <Space orientation="vertical" size={20} style={{ width: '100%' }}>
      <MapPreview row={map} />
      <Descriptions bordered size="small" column={1}>
        <Descriptions.Item
          label={intl.formatMessage({
            id: 'gameData.map.nameZhCn',
            defaultMessage: '中文名称',
          })}
        >
          {map.name_zh_cn ||
            intl.formatMessage({ id: 'common.notAvailable', defaultMessage: '暂无' })}
        </Descriptions.Item>
        <Descriptions.Item
          label={intl.formatMessage({ id: 'gameData.map.name', defaultMessage: '地图代码' })}
        >
          {map.map}
        </Descriptions.Item>
      </Descriptions>
      <div>
        <h4 style={{ margin: '0 0 8px' }}>
          {intl.formatMessage({
            id: 'gameData.map.npcs',
            defaultMessage: '本地图 NPC',
          })}
          {npcs.length ? `（${npcs.length}）` : ''}
        </h4>
        {npcs.length ? (
          <Space orientation="vertical" size={8} style={{ width: '100%' }}>
            {npcs.map((npc) => (
              <div
                key={npc.id}
                style={{
                  display: 'flex',
                  gap: 12,
                  alignItems: 'center',
                  padding: '8px 0',
                  borderBottom: '1px solid #f0f0f0',
                }}
              >
                {npc.image ? (
                  <Image
                    src={npc.image}
                    alt={npc.display_name}
                    width={40}
                    height={40}
                    style={{ objectFit: 'contain', imageRendering: 'pixelated' }}
                  />
                ) : (
                  <Empty
                    image={Empty.PRESENTED_IMAGE_SIMPLE}
                    description={false}
                    styles={{ root: { margin: 0 } }}
                  />
                )}
                <div>
                  <div>{npc.display_name || npc.name}</div>
                  <div style={{ color: '#8c8c8c' }}>
                    {npc.x}, {npc.y}
                  </div>
                </div>
              </div>
            ))}
          </Space>
        ) : (
          <Empty
            image={Empty.PRESENTED_IMAGE_SIMPLE}
            description={intl.formatMessage({
              id: 'gameData.map.npcs.empty',
              defaultMessage: '该地图没有可显示的 NPC',
            })}
          />
        )}
      </div>
    </Space>
  );
}
