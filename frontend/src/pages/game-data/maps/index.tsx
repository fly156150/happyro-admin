import {
  type ActionType,
  PageContainer,
  type ProColumns,
  ProTable,
} from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { useEffect, useRef, useState } from 'react';
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
  const actionRef = useRef<ActionType>(null);
  useEffect(() => {
    listMaps().then((result) => {
      setRows(result.data);
      actionRef.current?.reload();
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
    </PageContainer>
  );
}
