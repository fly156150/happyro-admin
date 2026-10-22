import { Table, Typography } from 'antd';
import type { ItemDropSource } from '@/api/catalog/items';

type DropRow = Omit<ItemDropSource, 'drops'> & {
  rate: number;
  count: number;
  key: string;
};

export function dropRows(
  sources: ItemDropSource[],
  method: 'normal' | 'mvp_reward',
): DropRow[] {
  return sources
    .flatMap(({ drops, ...monster }) =>
      drops
        .filter((drop) => drop.method === method)
        .map((drop) => ({
          ...monster,
          rate: drop.rate,
          count: drop.count,
          key: `${monster.id}:${drop.rate}`,
        })),
    )
    .sort((a, b) => b.rate - a.rate || a.id - b.id);
}

const kinds = { normal: '普通', mvp: 'MVP', mini: 'Mini' };
const columns = [
  { title: '名称', dataIndex: 'name' },
  { title: 'ID', dataIndex: 'id', width: 75 },
  { title: '等级', dataIndex: 'level', width: 65 },
  {
    title: '基础掉率',
    dataIndex: 'rate',
    width: 100,
    render: (rate: number) => `${rate / 100}%`,
  },
  {
    title: '魔物类型',
    dataIndex: 'kind',
    width: 90,
    render: (kind: ItemDropSource['kind']) => kinds[kind],
  },
  { title: '掉落槽数', dataIndex: 'count', width: 85 },
];

export default function ItemDropSources({
  sources,
}: {
  sources: ItemDropSource[];
}) {
  const groups = [
    { method: 'normal' as const, title: '普通掉落' },
    { method: 'mvp_reward' as const, title: 'MVP 奖励' },
  ]
    .map((group) => ({ ...group, rows: dropRows(sources, group.method) }))
    .filter((group) => group.rows.length);
  return (
    <section style={{ marginTop: 24 }} aria-label="掉落来源">
      {groups.length ? (
        groups.map((group) => (
          <div key={group.method}>
            <Typography.Title level={5}>{group.title}</Typography.Title>
            <Table
              size="small"
              bordered
              rowKey="key"
              columns={columns}
              dataSource={group.rows}
              scroll={{ x: 600 }}
              pagination={
                group.rows.length > 8
                  ? { pageSize: 8, showSizeChanger: false }
                  : false
              }
            />
          </div>
        ))
      ) : (
        <Table
          size="small"
          bordered
          columns={columns}
          dataSource={[]}
          pagination={false}
          scroll={{ x: 600 }}
          locale={{ emptyText: '无掉落信息' }}
        />
      )}
    </section>
  );
}
