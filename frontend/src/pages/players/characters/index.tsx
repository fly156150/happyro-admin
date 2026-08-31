import { PageContainer, ProTable, type ProColumns } from '@ant-design/pro-components';
import { Tag } from 'antd';
import { useIntl } from '@umijs/max';
import { listCharacters } from '@/services/players/queries';
import { jobMappings } from '@/data/game/jobs';
import { mapMappings } from '@/data/game/maps';

type Character = Record<string, unknown>;
export default function Characters() {
  const intl = useIntl(); const t = (id: string, fallback: string) => intl.formatMessage({ id, defaultMessage: fallback });
  const columns: ProColumns<Character>[] = [
    { title: t('players.character.id', '角色 ID'), dataIndex: 'char_id', search: false },
    { title: t('players.account.username', '用户名'), dataIndex: 'username' },
    { title: t('players.character.name', '角色名'), dataIndex: 'name' },
    { title: t('players.character.class', '职业'), dataIndex: 'class', search: false, render: (_, row) => t(jobMappings[Number(row.class)] ?? 'players.job.unknown', '其他职业') },
    { title: t('players.character.level', '等级'), dataIndex: 'base_level', search: false },
    { title: t('players.character.status', '状态'), dataIndex: 'online', valueEnum: { 0: t('players.character.offline', '离线'), 1: t('players.character.online', '在线') }, search: false, render: (_, row) => <Tag color={Number(row.online) === 1 ? 'success' : 'default'}>{Number(row.online) === 1 ? t('players.character.online', '在线') : t('players.character.offline', '离线')}</Tag> },
    { title: t('players.character.map', '地图'), dataIndex: 'last_map', search: false, render: (_, row) => t(mapMappings[String(row.last_map)] ?? 'players.map.unknown', '未知地图') },
  ];
  return <PageContainer title={t('players.characters.title', '游戏角色')}><ProTable<Character> rowKey="char_id" columns={columns} request={async (params) => { const result = await listCharacters(params); return { data: result.data, total: result.total, success: true }; }} /></PageContainer>;
}
