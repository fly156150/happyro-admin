import {
  ProForm,
  ProFormDigit,
  ProFormText,
  ProFormTextArea,
} from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { Select } from 'antd';
import { useCallback, useEffect, useRef, useState } from 'react';
import {
  searchItemGrantItems,
  searchItemGrantTargets,
} from '@/services/operations/item-grants';

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
        label: `${item.names[intl.locale] || item.names['en-US'] || item.aegis_name} · ID ${item.item_id}`,
        value: item.item_id,
      }));
    },
    [intl.locale],
  );
  const loadCharacters = useCallback(async (target: string) => {
    const response = await searchItemGrantTargets(target);
    return response.data.map((character) => ({
      label: `${character.name} · ID ${character.char_id} · ${character.username}`,
      value: character.char_id,
    }));
  }, []);
  const itemOptions = useRemoteOptions(loadItems);
  const characterOptions = useRemoteOptions(loadCharacters);

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
            showSearch
            filterOption={false}
            loading={itemOptions.loading}
            onSearch={itemOptions.search}
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
      <ProForm.Item
        name="char_id"
        label={t('operations.itemGrants.target', '角色')}
        rules={[{ required: true }]}
      >
        <Select
          allowClear
          showSearch
          filterOption={false}
          loading={characterOptions.loading}
          onSearch={characterOptions.search}
          options={characterOptions.options}
          placeholder={t(
            'operations.itemGrants.targetPlaceholder',
            '输入角色 ID、角色名或用户名',
          )}
        />
      </ProForm.Item>
      <ProFormDigit
        name="amount"
        label={t('operations.itemGrants.amount', '数量')}
        min={1}
        max={30000}
        fieldProps={{ precision: 0 }}
        initialValue={1}
        rules={[{ required: true }]}
      />
      <ProFormText
        name="title"
        label={t('operations.itemGrants.titleField', '邮件标题')}
        initialValue={t('operations.itemGrants.defaultTitle', '物品发放')}
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
  );
}
