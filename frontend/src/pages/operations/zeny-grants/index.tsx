import {
  PageContainer,
  ProForm,
  ProFormDigit,
} from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { Alert, App } from 'antd';
import { useRef } from 'react';
import CharacterGrantTargetField from '@/components/CharacterGrantTargetField';
import { grantZeny } from '@/services/operations/item-grants';
import { createIdempotencyKey } from '@/utils/idempotency';

export default function ZenyGrants() {
  const idempotencyKey = useRef(createIdempotencyKey());
  const intl = useIntl();
  const { message } = App.useApp();
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });

  return (
    <PageContainer title={t('operations.zenyGrants.title', 'Zeny 发放')}>
      <ProForm
        onFinish={async (values) => {
          await grantZeny({
            char_id: Number(values.char_id),
            amount: Number(values.amount),
            idempotency_key: idempotencyKey.current,
          });
          message.success(t('operations.itemGrants.zenySuccess', 'Zeny 已发放'));
          idempotencyKey.current = createIdempotencyKey();
          return true;
        }}
      >
        <Alert
          type="info"
          showIcon
          title={t(
            'operations.itemGrants.zenyOnlineOnly',
            'Zeny 将直接发放给角色，仅支持当前在线角色。',
          )}
          style={{ marginBottom: 16 }}
        />
        <CharacterGrantTargetField />
        <ProFormDigit
          name="amount"
          label="Zeny"
          min={1}
          max={2147483647}
          fieldProps={{ precision: 0 }}
          initialValue={100000}
          rules={[{ required: true }]}
        />
      </ProForm>
    </PageContainer>
  );
}
