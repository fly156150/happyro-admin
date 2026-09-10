import { ProForm } from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { Select } from 'antd';
import { useCallback, useEffect, useRef, useState } from 'react';
import { searchItemGrantTargets } from '@/services/operations/item-grants';

export default function CharacterGrantTargetField() {
  const intl = useIntl();
  const [options, setOptions] = useState<
    Array<{ label: string; value: number }>
  >([]);
  const [loading, setLoading] = useState(false);
  const timer = useRef<ReturnType<typeof setTimeout> | undefined>(undefined);
  const requestId = useRef(0);
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });

  useEffect(() => () => clearTimeout(timer.current), []);
  const search = useCallback((value: string) => {
    clearTimeout(timer.current);
    const target = value.trim();
    const currentRequest = ++requestId.current;
    if (!target) {
      setOptions([]);
      setLoading(false);
      return;
    }
    setLoading(true);
    timer.current = setTimeout(async () => {
      try {
        const response = await searchItemGrantTargets(target);
        if (requestId.current === currentRequest) {
          setOptions(
            response.data.map((character) => ({
              label: `${character.name} · ID ${character.char_id} · ${character.username}`,
              value: character.char_id,
            })),
          );
        }
      } catch {
        if (requestId.current === currentRequest) setOptions([]);
      } finally {
        if (requestId.current === currentRequest) setLoading(false);
      }
    }, 300);
  }, []);

  return (
    <ProForm.Item
      name="char_id"
      label={t('operations.itemGrants.target', '角色')}
      rules={[{ required: true }]}
    >
      <Select
        allowClear
        showSearch={{ filterOption: false, onSearch: search }}
        loading={loading}
        options={options}
        placeholder={t(
          'operations.itemGrants.targetPlaceholder',
          '输入角色 ID、角色名或用户名',
        )}
      />
    </ProForm.Item>
  );
}
