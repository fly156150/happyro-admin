import {
  ProForm,
  ProFormDigit,
  ProFormText,
  ProFormTextArea,
  ProFormDependency,
  ProFormRadio,
} from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { Select } from 'antd';
import { useCallback, useEffect, useRef, useState } from 'react';
import CharacterGrantTargetField from '@/components/CharacterGrantTargetField';
import { searchItemGrantItems } from '@/services/operations/item-grants';

type Props = {
  itemId?: number;
  onItemChange?: (itemId?: number) => void;
};

type SelectOption = {
  label: string;
  value: number;
};

function useRemoteOptions(loader: (target: string) => Promise<SelectOption[]>) {
  const [options, setOptions] = useState<SelectOption[]>([]);
  const [loading, setLoading] = useState(false);
  const timer = useRef<ReturnType<typeof setTimeout> | undefined>(undefined);
  const requestId = useRef(0);

  useEffect(() => () => clearTimeout(timer.current), []);

  const search = useCallback(
    (value: string) => {
      clearTimeout(timer.current);
      const target = value.trim();
      const currentRequest = ++requestId.current;
      if (!target) {
        setOptions([]);
        setLoading(false);
        return;
      }

      setLoading(true);
      setOptions([]);
      timer.current = setTimeout(async () => {
        try {
          const result = await loader(target);
          if (requestId.current === currentRequest) setOptions(result);
        } catch {
          if (requestId.current === currentRequest) setOptions([]);
        } finally {
          if (requestId.current === currentRequest) setLoading(false);
        }
      }, 300);
    },
    [loader],
  );

  return { loading, options, search };
}

export default function ItemGrantFormFields({ itemId, onItemChange }: Props) {
  const intl = useIntl();
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });
  const loadItems = useCallback(
    async (target: string) => {
      const response = await searchItemGrantItems(target);
      return response.data.map((item) => ({
        label:
          item.names[intl.locale] ||
          item.names['en-US'] ||
          item.aegis_name ||
          '未命名物品',
        value: item.item_id,
      }));
    },
    [intl.locale],
  );
  const itemOptions = useRemoteOptions(loadItems);

  return (
    <>
      {itemId === undefined ? (
        <ProForm.Item
          name="item_id"
          label={t('operations.itemGrants.item', '物品')}
          rules={[{ required: true }]}
        >
          <Select
            allowClear
            showSearch={{
              filterOption: false,
              onSearch: itemOptions.search,
            }}
            loading={itemOptions.loading}
            onChange={onItemChange}
            options={itemOptions.options}
            placeholder={t(
              'operations.itemGrants.itemPlaceholder',
              '输入物品 ID 或名称',
            )}
          />
        </ProForm.Item>
      ) : (
        <ProForm.Item
          name="item_id"
          initialValue={itemId}
          hidden
          rules={[{ required: true }]}
        >
          <input type="hidden" />
        </ProForm.Item>
      )}
      <CharacterGrantTargetField />
      <ProFormRadio.Group
        name="delivery"
        label={t('operations.itemGrants.delivery', '发放方式')}
        initialValue="mail"
        radioType="button"
        options={[
          {
            label: t('operations.itemGrants.delivery.mail', '邮件'),
            value: 'mail',
          },
          {
            label: t('operations.itemGrants.delivery.inventory', '背包'),
            value: 'inventory',
          },
        ]}
      />
      <ProFormDigit
        name="amount"
        label={t('operations.itemGrants.amount', '数量')}
        min={1}
        max={30000}
        fieldProps={{ precision: 0 }}
        initialValue={1}
        rules={[{ required: true }]}
      />
      <ProFormDependency name={['delivery']}>
        {({ delivery }) =>
          delivery === 'inventory' ? (
            <div>
              {t(
                'operations.itemGrants.inventoryOnlineOnly',
                '背包发放仅支持当前在线角色；装备会自动使用放大镜鉴定后发放。',
              )}
            </div>
          ) : (
            <>
              <ProFormText
                name="title"
                label={t('operations.itemGrants.titleField', '邮件标题')}
                initialValue={t(
                  'operations.itemGrants.defaultTitle',
                  '物品发放',
                )}
                rules={[{ required: true, max: 45 }]}
              />
              <ProFormTextArea
                name="message"
                label={t('operations.itemGrants.message', '邮件内容')}
                initialValue={t(
                  'operations.itemGrants.defaultMessage',
                  '管理员向你发放了物品。',
                )}
                fieldProps={{ autoSize: { minRows: 3, maxRows: 6 } }}
                rules={[{ required: true, max: 500 }]}
              />
            </>
          )
        }
      </ProFormDependency>
    </>
  );
}
