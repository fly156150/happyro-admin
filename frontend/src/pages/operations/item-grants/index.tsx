import { PageContainer, ProForm } from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { App, Image, theme } from 'antd';
import { useRef, useState } from 'react';
import ItemGrantFormFields from '@/components/ItemGrantFormFields';
import { mailItem } from '@/services/operations/item-grants';
import { createIdempotencyKey } from '@/utils/idempotency';

type ItemImageSource = 'illustration' | 'icon' | 'missing';

export default function ItemGrants() {
  const [selectedItemId, setSelectedItemId] = useState<number>();
  const [itemImageSource, setItemImageSource] =
    useState<ItemImageSource>('illustration');
  const idempotencyKey = useRef(createIdempotencyKey());
  const intl = useIntl();
  const { message } = App.useApp();
  const { token } = theme.useToken();
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });

  return (
    <PageContainer title={t('operations.itemGrants.title', '发放物品')}>
      <ProForm
        onFinish={async (values) => {
          await mailItem({
            item_id: Number(values.item_id),
            char_id: Number(values.char_id),
            amount: Number(values.amount),
            title: String(values.title),
            message: String(values.message),
            bound: Boolean(values.bound),
            idempotency_key: idempotencyKey.current,
          });
          message.success(t('operations.itemGrants.success', '邮件已发送'));
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
    </PageContainer>
  );
}
