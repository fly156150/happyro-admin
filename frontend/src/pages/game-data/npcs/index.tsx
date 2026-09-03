import {
  PageContainer,
  ProTable,
  type ProColumns,
  type ActionType,
} from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { useEffect, useRef, useState } from 'react';
import { listNpcs } from './service';

export default function Npcs() {
  const intl = useIntl();
  const [rows, setRows] = useState<
    {
      map: string;
      x: number;
      y: number;
      name: string;
      name_zh_cn?: string;
      map_name_zh_cn?: string;
      image?: string;
    }[]
  >([]);
  const actionRef = useRef<ActionType>(null);
  useEffect(() => {
    listNpcs().then((result) => {
      setRows(result.data);
      actionRef.current?.reload();
    });
  }, []);
  const columns: ProColumns<(typeof rows)[number]>[] = [
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
        row.name_zh_cn ||
        intl.formatMessage({
          id: 'common.notAvailable',
          defaultMessage: '暂无',
        }),
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
  ];
  return (
    <PageContainer
      title={intl.formatMessage({
        id: 'menu.gameData.npcs',
        defaultMessage: 'NPC 查询',
      })}
    >
      <ProTable
        rowKey={(row) => `${row.map}-${row.x}-${row.y}-${row.name}`}
        columns={columns}
        actionRef={actionRef}
        request={async (params) => {
          const q = String(params.name ?? '').toLowerCase();
          const zhName = String(params.name_zh_cn ?? '').toLowerCase();
          const map = String(params.map ?? '').toLowerCase();
          const data = rows.filter(
            (row) =>
              (!q || row.name.toLowerCase().includes(q)) &&
              (!zhName || row.name_zh_cn?.toLowerCase().includes(zhName)) &&
              (!map || row.map.toLowerCase().includes(map)),
          );
          return { data, total: data.length, success: true };
        }}
        pagination={{ pageSize: 20 }}
      />
    </PageContainer>
  );
}
