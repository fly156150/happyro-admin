import { useAccess, useIntl } from '@umijs/max';
import { Empty, Table } from 'antd';
import CharacterNavigationModal from '@/components/CharacterNavigationModal';
import { monsterSpawnMapMappings } from '@/data/game/monster-spawn-maps';
import spawnCatalog from '@/data/game/monster-spawns.json';

type Spawn = { map: string; x: number; y: number; count: number };
const spawns: Record<string, Spawn[]> = spawnCatalog.monsters;

export default function MonsterLocations({ monsterId }: { monsterId: number }) {
  const intl = useIntl();
  const access = useAccess();
  const locations = (spawns[String(monsterId)] ?? []).map((spawn) => ({
    ...spawn,
    name: intl.formatMessage({
      id: monsterSpawnMapMappings[spawn.map],
      defaultMessage: spawn.map,
    }),
  }));
  return (
    <section aria-label="出现地图">
      {locations.length ? (
        <Table
          size="small"
          rowKey="map"
          pagination={false}
          dataSource={locations}
          columns={[
            {
              title: '地图',
              dataIndex: 'name',
              render: (_, spawn) => (
                <div>
                  <div>{spawn.name}</div>
                  <div className="text-xs opacity-60">{spawn.map}</div>
                </div>
              ),
            },
            {
              title: '常驻数量',
              dataIndex: 'count',
              width: 90,
              render: (count: number) => `${count} 只`,
            },
            ...(access.canGameControl && access.canViewPlayers
              ? [
                  {
                    title: '操作',
                    key: 'actions',
                    width: 100,
                    render: (_: unknown, spawn: Spawn & { name: string }) => (
                      <CharacterNavigationModal
                        map={spawn.map}
                        mapName={spawn.name}
                        name={spawn.name}
                        x={spawn.x}
                        y={spawn.y}
                      />
                    ),
                  },
                ]
              : []),
          ]}
        />
      ) : (
        <Empty
          image={Empty.PRESENTED_IMAGE_SIMPLE}
          description="暂无常驻刷新地图"
        />
      )}
    </section>
  );
}
