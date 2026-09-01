import { EyeOutlined } from '@ant-design/icons';
import { PageContainer, ProTable, type ProColumns } from '@ant-design/pro-components';
import { Button, Descriptions, Drawer, Tag } from 'antd';
import { useIntl } from '@umijs/max';
import { useState } from 'react';
import { getItem, itemName, listItems, type GameItem } from '@/services/resources/items';

export default function Items() {
  const intl = useIntl();
  const [detail, setDetail] = useState<GameItem>();
  const t = (id: string, fallback: string) => intl.formatMessage({ id, defaultMessage: fallback });
  const locale = intl.locale;
  const columns: ProColumns<GameItem>[] = [
    { title: t('resources.item.icon', '图标'), dataIndex: 'illustration', search: false, width: 72, render: (_, row) => <span style={{ display: 'inline-flex', width: 48, height: 64, alignItems: 'center', justifyContent: 'center', background: '#fff', overflow: 'hidden' }}><img src={row.illustration || row.icon} alt={itemName(row, locale)} width={48} height={64} style={{ objectFit: 'contain', display: 'block' }} /></span> },
    { title: t('resources.item.id', '物品 ID'), dataIndex: 'Id', search: false, width: 100 },
    { title: t('resources.item.name', '名称'), dataIndex: 'names', render: (_, row) => itemName(row, locale) },
    { title: t('resources.item.aegisName', 'AegisName'), dataIndex: 'AegisName' },
    { title: t('resources.item.type', '类型'), dataIndex: 'Type' },
    { title: t('resources.item.weight', '重量'), dataIndex: 'Weight', search: false },
    { title: t('common.actions', '操作'), valueType: 'option', render: (_, row) => <Button type="link" icon={<EyeOutlined />} onClick={async () => setDetail((await getItem(row.Id)).data)}>{t('common.detail', '详情')}</Button> },
  ];
  return <PageContainer title={t('resources.items.title', '物品图鉴')}><ProTable<GameItem> rowKey="Id" columns={columns} request={async (params) => { const result = await listItems(params); return { data: result.data, total: result.total, success: true }; }} /><Drawer title={detail ? itemName(detail, locale) : t('resources.item.detail', '物品详情')} open={!!detail} onClose={() => setDetail(undefined)} width={520}>{detail && <><img src={detail.illustration || detail.icon} alt={itemName(detail, locale)} width={75} height={100} style={{ objectFit: 'contain', display: 'block', margin: '0 auto 16px', imageRendering: 'auto' }} /><Descriptions column={1} bordered size="small"><Descriptions.Item label={t('resources.item.id', '物品 ID')}>{detail.Id}</Descriptions.Item><Descriptions.Item label={t('resources.item.name', '名称')}>{itemName(detail, locale)}</Descriptions.Item><Descriptions.Item label={t('resources.item.aegisName', 'AegisName')}>{detail.AegisName || '-'}</Descriptions.Item><Descriptions.Item label={t('resources.item.type', '类型')}><Tag>{detail.Type || '-'}</Tag></Descriptions.Item><Descriptions.Item label={t('resources.item.buy', '买入价')}>{detail.Buy ?? '-'}</Descriptions.Item><Descriptions.Item label={t('resources.item.sell', '卖出价')}>{detail.Sell ?? '-'}</Descriptions.Item><Descriptions.Item label={t('resources.item.description', '说明')}>{detail.description?.length ? detail.description.map((line) => <div key={line}>{line}</div>) : t('resources.item.noDescription', '暂无说明')}</Descriptions.Item><Descriptions.Item label={t('resources.item.script', '服务器脚本')}>{detail.Script || '-'}</Descriptions.Item></Descriptions></>}</Drawer></PageContainer>;
}
