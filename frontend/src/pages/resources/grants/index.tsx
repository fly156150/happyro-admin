import { PageContainer, ProForm, ProFormDigit, ProFormText } from '@ant-design/pro-components';
import { App } from 'antd';
import { useIntl } from '@umijs/max';
import { mailItem } from '@/services/resources/grants';

export default function Grants() {
  const intl = useIntl();
  const { message } = App.useApp();
  const t = (id: string, fallback: string) => intl.formatMessage({ id, defaultMessage: fallback });

  return <PageContainer title={t('resources.grants.title', '发放物品')}><ProForm onFinish={async (values) => { await mailItem({ ...values, bound: Boolean(values.bound) }); message.success(t('resources.grants.success', '邮件已发送')); }}><ProFormDigit name="item_id" label={t('resources.item.id', '物品 ID')} min={1} fieldProps={{ precision: 0 }} rules={[{ required: true }]} /><ProFormDigit name="char_id" label={t('resources.grants.charId', '角色 ID')} min={1} fieldProps={{ precision: 0 }} rules={[{ required: true }]} /><ProFormDigit name="amount" label={t('resources.grants.amount', '数量')} min={1} max={30000} fieldProps={{ precision: 0 }} initialValue={1} rules={[{ required: true }]} /><ProFormText name="title" label={t('resources.grants.titleField', '邮件标题')} initialValue={t('resources.grants.defaultTitle', '物品发放')} rules={[{ required: true, max: 45 }]} /><ProFormText name="message" label={t('resources.grants.message', '邮件内容')} initialValue={t('resources.grants.defaultMessage', '管理员向你发放了物品。')} rules={[{ required: true, max: 500 }]} /></ProForm>;</PageContainer>;
}
