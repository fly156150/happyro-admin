import { EyeOutlined } from '@ant-design/icons';
import {
  PageContainer,
  type ProColumns,
  ProTable,
} from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import {
  Alert,
  Button,
  Descriptions,
  Drawer,
  Empty,
  Image,
  Skeleton,
  Space,
  Tag,
} from 'antd';
import { useEffect, useState } from 'react';
import type { GameDataNpc } from '@/pages/game-data/npcs/npcCatalog';
import { listMapNpcs } from '@/pages/game-data/npcs/service';
import { listMaps, type MapRow } from './service';

function MapPreview({ row, large = false }: { row: MapRow; large?: boolean }) {
  const intl = useIntl();
  const [failed, setFailed] = useState(false);
  if (row.image && !failed) {
    return (
      <img
        src={row.image}
        alt={row.name_zh_cn || row.map}
        onError={() => setFailed(true)}
        style={{
          width: large ? '100%' : 96,
          height: large ? 320 : 64,
          objectFit: 'contain',
          borderRadius: 4,
          imageRendering: row.image_kind === 'terrain' ? 'pixelated' : 'auto',
        }}
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
  const [npcs, setNpcs] = useState<GameDataNpc[]>([]);
  const [npcsLoading, setNpcsLoading] = useState(false);
  const [detail, setDetail] = useState<MapRow>();
  const [error, setError] = useState('');
  useEffect(() => {
    if (!detail) {
      setNpcs([]);
      return;
    }
    let active = true;
    setNpcsLoading(true);
    listMapNpcs(detail.map)
      .then((result) => {
        if (active) {
          setNpcs(result.data);
          setError('');
        }
      })
      .catch(() => {
        if (active) {
          setNpcs([]);
          setError('NPC 列表加载失败，地图详情暂不包含 NPC。');
        }
      })
      .finally(() => {
        if (active) {
          setNpcsLoading(false);
        }
      });
    return () => {
      active = false;
    };
  }, [detail]);
  const columns: ProColumns<MapRow>[] = [
    {
      title: '地图范围',
      dataIndex: 'scope',
      valueType: 'select',
      hideInTable: true,
      initialValue: 'game',
      valueEnum: { game: '游戏可用地图', all: '全部服务端地图' },
      fieldProps: { allowClear: false },
    },
    {
      title: intl.formatMessage({
        id: 'gameData.map.image',
        defaultMessage: '地图',
      }),
      dataIndex: 'image',
      search: false,
      width: 120,
      render: (_, row) => (
        <Button
          type="text"
          aria-label={`查看${row.name_zh_cn || row.map}详情`}
          onClick={() => setDetail(row)}
          style={{ height: 'auto', padding: 0 }}
        >
          <MapPreview row={row} />
        </Button>
      ),
    },
    {
      title: intl.formatMessage({
        id: 'gameData.map.id',
        defaultMessage: '地图 ID',
      }),
      dataIndex: 'id',
      search: false,
      hideInTable: true,
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
      title: '游戏支持',
      dataIndex: 'supported',
      search: false,
      render: (_, row) => (
        <Tag color={row.supported ? 'success' : 'default'}>
          {row.supported ? '支持' : '不支持'}
        </Tag>
      ),
    },
    {
      title: intl.formatMessage({
        id: 'common.actions',
        defaultMessage: '操作',
      }),
      valueType: 'option',
      width: 90,
      search: false,
      render: (_, row) => (
        <Button
          type="link"
          icon={<EyeOutlined />}
          onClick={() => setDetail(row)}
        >
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
        headerTitle={error ? <Alert type="error" title={error} /> : undefined}
        rowKey="map"
        columns={columns}
        request={async (params) => {
          const { data, total } = await listMaps({
            scope: params.scope === 'all' ? 'all' : 'game',
            map: params.map,
            name_zh_cn: params.name_zh_cn,
            page: params.current,
            perPage: params.pageSize,
          });
          return { data, total, success: true };
        }}
        pagination={{ pageSize: 20 }}
      />
      <Drawer
        title={
          detail?.name_zh_cn ||
          detail?.map ||
          intl.formatMessage({
            id: 'gameData.map.detail',
            defaultMessage: '地图详情',
          })
        }
        open={Boolean(detail)}
        onClose={() => setDetail(undefined)}
        size={560}
      >
        {detail ? (
          <MapDetails map={detail} npcs={npcs} loading={npcsLoading} />
        ) : null}
      </Drawer>
    </PageContainer>
  );
}

function MapDetails({
  map,
  npcs,
  loading,
}: {
  map: MapRow;
  npcs: GameDataNpc[];
  loading: boolean;
}) {
  const intl = useIntl();
  return (
    <Space orientation="vertical" size={20} style={{ width: '100%' }}>
      <MapPreview row={map} large />
      <Descriptions bordered size="small" column={1}>
        <Descriptions.Item label="地图编号">
          {map.id ?? '暂无'}
        </Descriptions.Item>
        <Descriptions.Item label="预览类型">
          {map.image_kind === 'terrain'
            ? '地形图'
            : map.image
              ? '地图图片'
              : '暂无图片'}
        </Descriptions.Item>
        <Descriptions.Item
          label={intl.formatMessage({
            id: 'gameData.map.nameZhCn',
            defaultMessage: '中文名称',
          })}
        >
          {map.name_zh_cn ||
            intl.formatMessage({
              id: 'common.notAvailable',
              defaultMessage: '暂无',
            })}
        </Descriptions.Item>
        <Descriptions.Item
          label={intl.formatMessage({
            id: 'gameData.map.name',
            defaultMessage: '地图代码',
          })}
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
        {loading ? (
          <Skeleton active paragraph={{ rows: 4 }} title={false} />
        ) : npcs.length ? (
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
                    style={{
                      objectFit: 'contain',
                      imageRendering: 'pixelated',
                    }}
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
