import {
  PageContainer,
  type ProColumns,
  ProTable,
} from '@ant-design/pro-components';
import { useAccess, useIntl } from '@umijs/max';
import { Descriptions, Drawer, Tag } from 'antd';
import { useState } from 'react';
import CharacterMaintenanceModal from '@/components/CharacterMaintenanceModal';
import { jobMappings } from '@/data/game/jobs';
import { mapMappings } from '@/data/game/maps';
import { getCharacter, listCharacters } from '@/services/players/queries';

type Character = Record<string, unknown>;
export default function Characters() {
  const intl = useIntl();
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });
  const access = useAccess();
  const [detail, setDetail] = useState<Record<string, unknown> | null>(null);
  const columns: ProColumns<Character>[] = [
    {
      title: t('players.character.id', '角色 ID'),
      dataIndex: 'char_id',
      search: false,
    },
    { title: t('players.account.username', '用户名'), dataIndex: 'username' },
    {
      title: t('players.character.name', '角色名'),
      dataIndex: 'name',
      render: (_, row) => (
        <a
          onClick={async () =>
            setDetail((await getCharacter(Number(row.char_id))).data)
          }
        >
          {String(row.name)}
        </a>
      ),
    },
    {
      title: t('players.character.class', '职业'),
      dataIndex: 'class',
      search: false,
      render: (_, row) =>
        t(jobMappings[Number(row.class)] ?? 'players.job.unknown', '其他职业'),
    },
    {
      title: t('players.character.level', '等级'),
      dataIndex: 'base_level',
      search: false,
    },
    {
      title: t('players.character.status', '状态'),
      dataIndex: 'online',
      valueEnum: {
        0: t('players.character.offline', '离线'),
        1: t('players.character.online', '在线'),
      },
      search: false,
      render: (_, row) => (
        <Tag color={Number(row.online) === 1 ? 'success' : 'default'}>
          {Number(row.online) === 1
            ? t('players.character.online', '在线')
            : t('players.character.offline', '离线')}
        </Tag>
      ),
    },
    {
      title: t('players.character.map', '地图'),
      dataIndex: 'last_map',
      search: false,
      render: (_, row) =>
        t(
          mapMappings[String(row.last_map)] ?? 'players.map.unknown',
          '未知地图',
        ),
    },
    ...(access.canGameControl && access.canViewPlayers
      ? [
          {
            title: t('common.actions', '操作'),
            valueType: 'option' as const,
            render: (_value: unknown, row: Character) => (
              <CharacterMaintenanceModal
                charId={Number(row.char_id)}
                characterName={String(row.name)}
              />
            ),
          },
        ]
      : []),
  ];
  return (
    <PageContainer title={t('players.characters.title', '游戏角色')}>
      <ProTable<Character>
        rowKey="char_id"
        columns={columns}
        request={async (params) => {
          const result = await listCharacters(params);
          return { data: result.data, total: result.total, success: true };
        }}
      />
      <Drawer
        title={
          detail
            ? `${t('players.character.detail', '角色详情')} · ${String(detail.name)}`
            : ''
        }
        open={Boolean(detail)}
        onClose={() => setDetail(null)}
        size="default"
      >
        {detail && (
          <Descriptions column={1} size="small">
            {[
              'char_id',
              'account_id',
              'username',
              'class',
              'base_level',
              'job_level',
              'base_exp',
              'job_exp',
              'zeny',
              'str',
              'agi',
              'vit',
              'int',
              'dex',
              'luk',
              'hp',
              'max_hp',
              'sp',
              'max_sp',
              'status_point',
              'skill_point',
              'last_map',
              'last_x',
              'last_y',
              'online',
              'last_login',
            ].map((key) => (
              <Descriptions.Item
                key={key}
                label={t(`players.character.detail.${key}`, key)}
              >
                {key === 'class'
                  ? t(
                      jobMappings[Number(detail[key])] ?? 'players.job.unknown',
                      '其他职业',
                    )
                  : key === 'last_map'
                    ? t(
                        mapMappings[String(detail[key])] ??
                          'players.map.unknown',
                        String(detail[key] ?? '-'),
                      )
                    : key === 'online'
                      ? t(
                          Number(detail[key]) === 1
                            ? 'players.character.online'
                            : 'players.character.offline',
                          Number(detail[key]) === 1 ? '在线' : '离线',
                        )
                      : String(detail[key] ?? '-')}
              </Descriptions.Item>
            ))}
          </Descriptions>
        )}
      </Drawer>
    </PageContainer>
  );
}
