import { useIntl } from '@umijs/max';
import { Tag, theme } from 'antd';
import { jobMappings } from '@/data/game/jobs';
import styles from './index.less';

const sections = [
  {
    title: '基础信息',
    fields: [
      'char_id',
      'account_id',
      'username',
      'class',
      'online',
      'last_login',
    ],
  },
  {
    title: '等级与点数',
    fields: [
      'base_level',
      'job_level',
      'base_exp',
      'job_exp',
      'status_point',
      'skill_point',
      'zeny',
    ],
  },
  { title: '基础属性', fields: ['str', 'agi', 'vit', 'int', 'dex', 'luk'] },
  {
    title: '四转特性',
    fields: ['pow', 'sta', 'wis', 'spl', 'con', 'crt', 'trait_point'],
  },
  {
    title: '生命状态',
    fields: ['hp', 'max_hp', 'sp', 'max_sp', 'ap', 'max_ap'],
  },
  {
    title: '所在位置',
    fields: ['last_map_name', 'last_map', 'last_x', 'last_y'],
  },
];

export default function CharacterDetails({
  character,
}: {
  character: Record<string, unknown>;
}) {
  const intl = useIntl();
  const { token } = theme.useToken();
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });
  const value = (field: string) => {
    const raw = character[field];
    if (raw === null || raw === undefined || raw === '') return '—';
    if (field === 'class')
      return jobMappings[Number(raw)]
        ? t(jobMappings[Number(raw)], `职业 ${raw}`)
        : `职业 ${raw}`;
    if (field === 'online')
      return (
        <Tag color={Number(raw) === 1 ? 'success' : 'default'}>
          {Number(raw) === 1 ? '在线' : '离线'}
        </Tag>
      );
    if (
      ['base_exp', 'job_exp', 'zeny', 'hp', 'max_hp', 'sp', 'max_sp'].includes(
        field,
      )
    )
      return Number(raw).toLocaleString(intl.locale);
    return String(raw);
  };
  return (
    <div
      className={styles.wrapper}
      style={{ borderColor: token.colorBorderSecondary }}
    >
      <table
        className={styles.table}
        aria-label="角色详情"
        style={{ color: token.colorText, background: token.colorBgContainer }}
      >
        <colgroup>
          <col style={{ width: '18%' }} />
          <col style={{ width: '32%' }} />
          <col style={{ width: '18%' }} />
          <col style={{ width: '32%' }} />
        </colgroup>
        {sections.map((section) => (
          <tbody key={section.title}>
            <tr>
              <th
                colSpan={4}
                scope="colgroup"
                className={styles.section}
                style={{
                  background: token.colorFillAlter,
                  borderColor: token.colorBorderSecondary,
                }}
              >
                {section.title}
              </th>
            </tr>
            {section.fields
              .filter((_, index) => index % 2 === 0)
              .map((field, index) => {
                const other = section.fields[index * 2 + 1];
                return (
                  <tr key={field}>
                    <th
                      scope="row"
                      style={{
                        color: token.colorTextSecondary,
                        borderColor: token.colorBorderSecondary,
                      }}
                    >
                      {t(`players.character.detail.${field}`, field)}
                    </th>
                    <td
                      colSpan={other ? 1 : 3}
                      style={{ borderColor: token.colorBorderSecondary }}
                    >
                      {value(field)}
                    </td>
                    {other && (
                      <>
                        <th
                          scope="row"
                          style={{
                            color: token.colorTextSecondary,
                            borderColor: token.colorBorderSecondary,
                          }}
                        >
                          {t(`players.character.detail.${other}`, other)}
                        </th>
                        <td style={{ borderColor: token.colorBorderSecondary }}>
                          {value(other)}
                        </td>
                      </>
                    )}
                  </tr>
                );
              })}
          </tbody>
        ))}
      </table>
    </div>
  );
}
