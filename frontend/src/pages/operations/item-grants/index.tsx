import {
  PageContainer,
  ProForm,
  ProFormDigit,
} from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { Alert, App, Image, Tabs, theme } from 'antd';
import { useRef, useState } from 'react';
import ItemGrantFormFields from '@/components/ItemGrantFormFields';
import CharacterGrantTargetField from '@/components/CharacterGrantTargetField';
import { grantItem, grantZeny } from '@/services/operations/item-grants';
import { createIdempotencyKey } from '@/utils/idempotency';

type ItemImageSource = 'illustration' | 'icon' | 'missing';

export default function ItemGrants() {
  const [selectedItemId, setSelectedItemId] = useState<number>();
  const [itemImageSource, setItemImageSource] =
    useState<ItemImageSource>('illustration');
  const idempotencyKey = useRef(createIdempotencyKey());
  const zenyIdempotencyKey = useRef(createIdempotencyKey());
  const intl = useIntl();
  const { message } = App.useApp();
  const { token } = theme.useToken();
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });

  return (
    <PageContainer title={t('operations.itemGrants.title', '发放物品')}>
      <Tabs
        items={[
          {
            key: 'items',
            label: t('operations.itemGrants.itemsTab', '物品邮件'),
            children: (
              <ProForm
                onFinish={async (values) => {
                  const delivery = values.delivery === 'inventory' ? 'inventory' : 'mail';
                  await grantItem({
                    item_id: Number(values.item_id),
                    char_id: Number(values.char_id),
                    amount: Number(values.amount),
                    title: values.title ? String(values.title) : undefined,
                    message: values.message ? String(values.message) : undefined,
                    bound: Boolean(values.bound),
                    delivery,
                    idempotency_key: idempotencyKey.current,
                  });
                  message.success(
                    delivery === 'inventory'
                      ? t('operations.itemGrants.inventorySuccess', '物品已发放到背包')
                      : t('operations.itemGrants.success', '邮件已发送'),
                  );
                  idempotencyKey.current = createIdempotencyKey();
                  return true;
                }}
              >
                <div
                  style={{
                    alignItems: 'center',
                    background: token.colorFillAlter,
                    border: `1px solid ${token.colorBorderSecondary}`,
                    borderRadius: token.borderRadius,
                    color: token.colorTextQuaternary,
                    display: 'flex',
                    width: 75,
                    height: 100,
                    justifyContent: 'center',
                    marginBottom: 16,
                    textAlign: 'center',
                  }}
                >
                  {selectedItemId === undefined ? (
                    <span style={{ fontSize: 12, lineHeight: 1.5 }}>
                      {t('gameData.item.image', '图片')}
                    </span>
                  ) : itemImageSource === 'missing' ? (
                    <span style={{ fontSize: 12, lineHeight: 1.5 }}>
                      {t('gameData.item.noImage', '暂无图片')}
                    </span>
                  ) : (
                    <Image
                      key={`${selectedItemId}-${itemImageSource}`}
                      src={`/api/game-data/items/${selectedItemId}/${itemImageSource}`}
                      alt={t('operations.itemGrants.item', '物品')}
                      width={75}
                      height={100}
                      preview
                      onError={() =>
                        setItemImageSource((current) =>
                          current === 'illustration' ? 'icon' : 'missing',
                        )
                      }
                      styles={{ image: { objectFit: 'contain' } }}
                    />
                  )}
                </div>
                <ItemGrantFormFields
                  onItemChange={(itemId) => {
                    setSelectedItemId(itemId);
                    setItemImageSource('illustration');
                  }}
                />
              </ProForm>
            ),
          },
          {
            key: 'zeny',
            label: 'Zeny',
            children: (
              <ProForm
                onFinish={async (values) => {
                  await grantZeny({
                    char_id: Number(values.char_id),
                    amount: Number(values.amount),
                    idempotency_key: zenyIdempotencyKey.current,
                  });
                  message.success(
                    t('operations.itemGrants.zenySuccess', 'Zeny 已发放'),
                  );
                  zenyIdempotencyKey.current = createIdempotencyKey();
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
            ),
          },
        ]}
      />
    </PageContainer>
  );
}
