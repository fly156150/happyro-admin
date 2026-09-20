import {
  PageContainer,
  ProTable,
  type ProColumns,
  type ActionType,
} from '@ant-design/pro-components';
import { CheckCircleOutlined, EyeOutlined } from '@ant-design/icons';
import {
  Button,
  Descriptions,
  Drawer,
  Empty,
  Image,
  Space,
  Tag,
  Typography,
} from 'antd';
import { useIntl, useAccess } from '@umijs/max';
import CharacterNavigationModal from '@/components/CharacterNavigationModal';
import { useEffect, useRef, useState } from 'react';
import type { GameDataNpc, NpcVisibility } from './npcCatalog';
import { listNpcs } from './service';

export default function Npcs() {
  const access = useAccess();
  const intl = useIntl();
  const [visibility, setVisibility] = useState<NpcVisibility>('game');
  const [detail, setDetail] = useState<GameDataNpc>();
  const actionRef = useRef<ActionType>(null);
  useEffect(() => {
    actionRef.current?.reload();
  }, [visibility]);
  const columns: ProColumns<GameDataNpc>[] = [
    {
      title: intl.formatMessage({
        id: 'gameData.npc.visibility',
        defaultMessage: '显示范围',
      }),
      dataIndex: 'visibility',
      hideInTable: true,
      initialValue: 'game',
      valueType: 'select',
      fieldProps: {
        allowClear: false,
        options: [
          {
            value: 'game',
            label: intl.formatMessage({
              id: 'gameData.npc.visibility.game',
              defaultMessage: '游戏内可见',
            }),
          },
          {
            value: 'all',
            label: intl.formatMessage({
              id: 'gameData.npc.visibility.all',
              defaultMessage: '全部目录',
            }),
          },
        ],
        onChange: (value: string | number) =>
          setVisibility(value as NpcVisibility),
      },
    },
    {
      title: intl.formatMessage({
        id: 'gameData.npc.image',
        defaultMessage: '形象',
      }),
      dataIndex: 'image',
      search: false,
      width: 80,
      render: (_, row) =>
        row.image ? (
          <button
            type="button"
            aria-label={intl.formatMessage({
              id: 'gameData.npc.detail.open',
              defaultMessage: '查看 NPC 详情',
            })}
            onClick={() => setDetail(row)}
            style={{
              display: 'inline-flex',
              width: 48,
              height: 48,
              alignItems: 'center',
              justifyContent: 'center',
              padding: 0,
              border: 0,
              background: 'transparent',
              cursor: 'pointer',
            }}
          >
            <img
              src={row.image}
              alt={row.name}
              style={{
                width: 48,
                height: 48,
                maxWidth: 48,
                maxHeight: 48,
                objectFit: 'contain',
                imageRendering: 'pixelated',
              }}
            />
          </button>
        ) : null,
    },
    {
      title: intl.formatMessage({
        id: 'gameData.npc.map',
        defaultMessage: '地图',
      }),
      dataIndex: 'map',
      render: (_, row) =>
        row.map_name_zh_cn ? `${row.map_name_zh_cn} (${row.map})` : row.map,
    },
    {
      title: intl.formatMessage({
        id: 'gameData.npc.nameZhCn',
        defaultMessage: '中文名称',
      }),
      dataIndex: 'name_zh_cn',
      render: (_, row) => row.name_zh_cn || row.source_name,
    },
    {
      title: intl.formatMessage({
        id: 'gameData.npc.name',
        defaultMessage: 'NPC 原名',
      }),
      dataIndex: 'name',
    },
    {
      title: intl.formatMessage({
        id: 'gameData.npc.position',
        defaultMessage: '坐标',
      }),
      dataIndex: 'x',
      search: false,
      render: (_, row) => `${row.x}, ${row.y}`,
    },
    {
      title: intl.formatMessage({
        id: 'gameData.npc.status',
        defaultMessage: '状态',
      }),
      dataIndex: 'enabled',
      search: false,
      hideInTable: visibility === 'game',
      width: 120,
      render: (_, row) => (
        <Space size={4} wrap>
          {row.game_visible ? (
            <Tag icon={<CheckCircleOutlined />} color="success">
              {intl.formatMessage({
                id: 'gameData.npc.gameVisible',
                defaultMessage: '游戏内可见',
              })}
            </Tag>
          ) : (
            <Tag color={row.navigation ? 'error' : 'warning'}>
              {row.navigation
                ? intl.formatMessage({
                    id: 'gameData.npc.noImage',
                    defaultMessage: '无图片',
                  })
                : intl.formatMessage({
                    id: 'gameData.npc.noNavigation',
                    defaultMessage: '无导航',
                  })}
            </Tag>
          )}
          {row.dynamic ? (
            <Tag>
              {intl.formatMessage({
                id: 'gameData.npc.dynamic',
                defaultMessage: '动态',
              })}
            </Tag>
          ) : null}
        </Space>
      ),
    },
    {
      title: intl.formatMessage({
        id: 'common.actions',
        defaultMessage: '操作',
      }),
      valueType: 'option',
      width: 180,
      fixed: 'right',
      render: (_, row) => (
        <Space>
          <Button
            type="link"
            icon={<EyeOutlined />}
            onClick={() => setDetail(row)}
          >
            {intl.formatMessage({
              id: 'common.detail',
              defaultMessage: '详情',
            })}
          </Button>
          {access.canGameControl && access.canViewPlayers && row.navigation && (
            <CharacterNavigationModal
              map={row.map}
              name={row.display_name || row.name}
              x={row.x}
              y={row.y}
              npcClass={row.navigation.class}
            />
          )}
        </Space>
      ),
    },
  ];
  return (
    <PageContainer
      title={intl.formatMessage({
        id: 'menu.gameData.npcs',
        defaultMessage: 'NPC 查询',
      })}
    >
      <ProTable
        rowKey="id"
        columns={columns}
        actionRef={actionRef}
        request={async (params) => {
          const { data, total } = await listNpcs({
            map: params.map,
            name: params.name,
            name_zh_cn: params.name_zh_cn,
            visibility:
              (params.visibility as NpcVisibility | undefined) ?? visibility,
            page: params.current,
            perPage: params.pageSize,
          });
          return { data, total, success: true };
        }}
        pagination={{ pageSize: 20 }}
      />
      <Drawer
        title={
          detail?.display_name ||
          intl.formatMessage({
            id: 'gameData.npc.detail',
            defaultMessage: 'NPC 详情',
          })
        }
        open={Boolean(detail)}
        onClose={() => setDetail(undefined)}
        size={520}
      >
        {detail ? <NpcDetails npc={detail} /> : null}
      </Drawer>
    </PageContainer>
  );
}

function NpcDetails({ npc }: { npc: GameDataNpc }) {
  const intl = useIntl();
  const source = `${npc.source.path}:${npc.source.line}`;
  const mapName = npc.map_name_zh_cn
    ? `${npc.map_name_zh_cn} (${npc.map})`
    : npc.map;
  return (
    <Space orientation="vertical" size={20} style={{ width: '100%' }}>
      <div
        style={{
          display: 'flex',
          minHeight: 152,
          alignItems: 'center',
          justifyContent: 'center',
        }}
      >
        {npc.image ? (
          <Image
            src={npc.image}
            alt={npc.display_name}
            width={144}
            height={144}
            styles={{
              root: {
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
              },
            }}
            style={{ objectFit: 'contain', imageRendering: 'pixelated' }}
          />
        ) : (
          <Empty
            image={Empty.PRESENTED_IMAGE_SIMPLE}
            description={intl.formatMessage({
              id: 'gameData.npc.noImage',
              defaultMessage: '无图片',
            })}
          />
        )}
      </div>
      <Descriptions bordered size="small" column={1}>
        <Descriptions.Item
          label={intl.formatMessage({
            id: 'gameData.npc.nameZhCn',
            defaultMessage: '中文名称',
          })}
        >
          {npc.display_name}
        </Descriptions.Item>
        <Descriptions.Item
          label={intl.formatMessage({
            id: 'gameData.npc.name',
            defaultMessage: 'NPC 原名',
          })}
        >
          {npc.name}
        </Descriptions.Item>
        <Descriptions.Item
          label={intl.formatMessage({
            id: 'gameData.npc.map',
            defaultMessage: '地图',
          })}
        >
          {mapName}
        </Descriptions.Item>
        <Descriptions.Item
          label={intl.formatMessage({
            id: 'gameData.npc.position',
            defaultMessage: '坐标',
          })}
        >
          {npc.x}, {npc.y}
        </Descriptions.Item>
        <Descriptions.Item
          label={intl.formatMessage({
            id: 'gameData.npc.status',
            defaultMessage: '状态',
          })}
        >
          <Space wrap>
            <Tag color={npc.game_visible ? 'success' : 'default'}>
              {npc.game_visible
                ? intl.formatMessage({
                    id: 'gameData.npc.gameVisible',
                    defaultMessage: '游戏内可见',
                  })
                : intl.formatMessage({
                    id: 'gameData.npc.notGameVisible',
                    defaultMessage: '游戏内不可见',
                  })}
            </Tag>
            {npc.dynamic ? (
              <Tag>
                {intl.formatMessage({
                  id: 'gameData.npc.dynamic',
                  defaultMessage: '动态',
                })}
              </Tag>
            ) : null}
          </Space>
        </Descriptions.Item>
        <Descriptions.Item
          label={intl.formatMessage({
            id: 'gameData.npc.scriptType',
            defaultMessage: '脚本类型',
          })}
        >
          <Tag>{npc.type}</Tag>
        </Descriptions.Item>
        <Descriptions.Item
          label={intl.formatMessage({
            id: 'gameData.npc.displaySpriteId',
            defaultMessage: '显示形象 ID',
          })}
        >
          {npc.display_sprite_id ?? '-'}
        </Descriptions.Item>
        <Descriptions.Item
          label={intl.formatMessage({
            id: 'gameData.npc.scriptSpriteId',
            defaultMessage: '脚本 Sprite ID',
          })}
        >
          {npc.sprite_id ?? '-'}
        </Descriptions.Item>
        <Descriptions.Item
          label={intl.formatMessage({
            id: 'gameData.npc.navigationClass',
            defaultMessage: '传送 Class',
          })}
        >
          {npc.navigation?.class ?? '-'}
        </Descriptions.Item>
        <Descriptions.Item
          label={intl.formatMessage({
            id: 'gameData.npc.navigationId',
            defaultMessage: '导航 ID',
          })}
        >
          {npc.navigation?.id ?? '-'}
        </Descriptions.Item>
        <Descriptions.Item
          label={intl.formatMessage({
            id: 'gameData.npc.source',
            defaultMessage: '脚本来源',
          })}
        >
          <Typography.Text
            code
            copyable={{ text: source }}
            style={{ wordBreak: 'break-all' }}
          >
            {source}
          </Typography.Text>
        </Descriptions.Item>
        <Descriptions.Item
          label={intl.formatMessage({
            id: 'gameData.npc.instanceId',
            defaultMessage: '实例 ID',
          })}
        >
          <Typography.Text
            code
            copyable={{ text: npc.id }}
            style={{ wordBreak: 'break-all' }}
          >
            {npc.id}
          </Typography.Text>
        </Descriptions.Item>
      </Descriptions>
    </Space>
  );
}
