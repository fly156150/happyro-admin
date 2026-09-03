import { SendOutlined } from '@ant-design/icons';
import { ModalForm } from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { App, Button, Image } from 'antd';
import ItemGrantFormFields from '@/components/ItemGrantFormFields';
import { mailItem } from '@/services/operations/item-grants';

type Props = {
  itemId: number;
  itemName: string;
};

export default function ItemGrantModal({ itemId, itemName }: Props) {
  const intl = useIntl();
  const { message } = App.useApp();
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });

  return (
    <ModalForm
      key={`${itemId}-${intl.locale}`}
      title={intl.formatMessage(
        {
          id: 'operations.itemGrants.modalTitle',
          defaultMessage: '{name} · ID {id}',
        },
        { name: itemName, id: itemId },
      )}
      trigger={
        <Button type="link" icon={<SendOutlined />}>
          {t('operations.itemGrants.action', '发放')}
        </Button>
      }
      width={520}
      modalProps={{ destroyOnHidden: true }}
      onFinish={async (values) => {
        await mailItem({
          item_id: itemId,
          char_id: Number(values.char_id),
          amount: Number(values.amount),
          title: String(values.title),
          message: String(values.message),
          bound: false,
        });
        message.success(t('operations.itemGrants.success', '邮件已发送'));
        return true;
      }}
    >
      <div
        style={{
          display: 'flex',
          justifyContent: 'center',
          minHeight: 100,
          marginBottom: 16,
        }}
      >
        <Image
          src={`/api/game-data/items/${itemId}/illustration`}
          fallback={`/api/game-data/items/${itemId}/icon`}
          alt={itemName}
          width={75}
          height={100}
          preview
          styles={{ image: { objectFit: 'contain' } }}
        />
      </div>
      <ItemGrantFormFields itemId={itemId} />
    </ModalForm>
  );
}
