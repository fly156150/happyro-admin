import {
  PageContainer,
  ProTable,
  type ProColumns,
  type ActionType,
} from '@ant-design/pro-components';
import { CheckCircleOutlined } from '@ant-design/icons';
import { Space, Tag, Typography } from 'antd';
import { useIntl } from '@umijs/max';
import { useEffect, useRef, useState } from 'react';
import {
  filterAndSortNpcs,
  type GameDataNpc,
  type NpcVisibility,
} from './npcCatalog';
import { listNpcs } from './service';

export default function Npcs() {
  const intl = useIntl();
  const [rows, setRows] = useState<GameDataNpc[]>([]);
  const [visibility, setVisibility] = useState<NpcVisibility>('game');
  const actionRef = useRef<ActionType>(null);
  useEffect(() => {
    listNpcs().then((result) => {
      setRows(result.data);
    });
  }, []);
  useEffect(() => {
    actionRef.current?.reload();
  }, [rows, visibility]);
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
        onChange: (value: string | number) => setVisibility(value as NpcVisibility),
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
          <img
            src={row.image}
            alt={row.name}
            style={{
              width: 48,
              height: 48,
              objectFit: 'contain',
              imageRendering: 'pixelated',
            }}
          />
        ) : null,
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
                ? intl.formatMessage({ id: 'gameData.npc.noImage', defaultMessage: '无图片' })
                : intl.formatMessage({ id: 'gameData.npc.noNavigation', defaultMessage: '无导航' })}
            </Tag>
          )}
          {row.dynamic ? (
            <Tag>{intl.formatMessage({ id: 'gameData.npc.dynamic', defaultMessage: '动态' })}</Tag>
          ) : null}
        </Space>
      ),
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
      render: (_, row) =>
        row.name_zh_cn || row.source_name,
    },
    {
      title: intl.formatMessage({
        id: 'gameData.npc.type',
        defaultMessage: '脚本类型 / 形象 ID',
      }),
      dataIndex: 'type',
      search: false,
      hideInTable: visibility === 'game',
      width: 150,
      render: (_, row) => (
        <Space size={4} wrap>
          <Tag>{row.type}</Tag>
          <Typography.Text type="secondary">
            {row.display_sprite_id ?? row.sprite_id ?? 'N/A'}
          </Typography.Text>
        </Space>
      ),
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
        id: 'gameData.npc.source',
        defaultMessage: '脚本来源',
      }),
      dataIndex: ['source', 'path'],
      search: false,
      hideInTable: visibility === 'game',
      ellipsis: true,
      render: (_, row) => (
        <Typography.Text code copyable={{ text: `${row.source.path}:${row.source.line}` }}>
          {row.source.path}:{row.source.line}
        </Typography.Text>
      ),
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
          const data = filterAndSortNpcs(
            rows,
            {
              map: params.map,
              name: params.name,
              name_zh_cn: params.name_zh_cn,
            },
            (params.visibility as NpcVisibility | undefined) ?? visibility,
          );
          return { data, total: data.length, success: true };
        }}
        pagination={{ pageSize: 20 }}
      />
    </PageContainer>
  );
}
