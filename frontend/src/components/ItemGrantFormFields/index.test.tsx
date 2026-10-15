import {
  cleanup,
  fireEvent,
  render,
  screen,
  waitFor,
} from '@testing-library/react';
import { Button, Form } from 'antd';
import { afterEach, expect, it, vi } from 'vitest';
import ItemGrantFormFields from './index';
import { mailTextError } from './validation';

// Keep the real Ant Design form validation while adapting ProForm wrappers,
// whose locale imports cannot be loaded directly by this Node test environment.
vi.mock('@ant-design/pro-components', async () => {
  const { Form, Input, InputNumber, Radio } = await import('antd');
  const text = ({ fieldProps, ...props }: any) => (
    <Form.Item {...props}>
      <Input {...fieldProps} />
    </Form.Item>
  );
  return {
    ProForm: { Item: Form.Item },
    ProFormText: text,
    ProFormTextArea: ({ fieldProps, ...props }: any) => (
      <Form.Item {...props}>
        <Input.TextArea {...fieldProps} />
      </Form.Item>
    ),
    ProFormDigit: ({ min, max, fieldProps, ...props }: any) => (
      <Form.Item {...props}>
        <InputNumber min={min} max={max} {...fieldProps} />
      </Form.Item>
    ),
    ProFormRadio: {
      Group: ({ options, radioType: _radioType, ...props }: any) => (
        <Form.Item {...props}>
          <Radio.Group options={options} />
        </Form.Item>
      ),
    },
    ProFormDependency: ({ children }: any) => (
      <Form.Item noStyle shouldUpdate>
        {(form) => children(form.getFieldsValue())}
      </Form.Item>
    ),
  };
});
vi.mock('@umijs/max', () => ({
  useIntl: () => ({
    locale: 'zh-CN',
    formatMessage: ({ defaultMessage }: { defaultMessage: string }) =>
      defaultMessage,
  }),
}));
vi.mock('@/components/CharacterGrantTargetField', () => ({
  default: () => null,
}));
vi.mock('@/services/operations/item-grants', () => ({
  searchItemGrantItems: vi.fn(),
}));
afterEach(cleanup);

function form(type: string, amount: number, title = '物品发放') {
  const finish = vi.fn();
  render(
    <Form
      onFinish={finish}
      initialValues={{
        item_id: 9001,
        delivery: 'mail',
        amount,
        title,
        message: '邮件内容',
      }}
    >
      <ItemGrantFormFields itemId={9001} itemType={type} />
      <Button htmlType="submit">发送测试邮件</Button>
    </Form>,
  );
  return finish;
}

it('prevents multiple pet eggs and disables inventory delivery', async () => {
  const finish = form('PetEgg', 2);
  expect(screen.getByRole('radio', { name: '背包' })).toBeDisabled();
  expect(screen.getByRole('spinbutton', { name: '数量' })).toHaveAttribute(
    'aria-valuemax',
    '1',
  );
  fireEvent.click(screen.getByRole('button', { name: '发送测试邮件' }));
  await waitFor(() =>
    expect(document.querySelector('.ant-form-item-has-error')).not.toBeNull(),
  );
  expect(finish).not.toHaveBeenCalled();
});

it('keeps ordinary multi-item mail delivery available', async () => {
  const finish = form('Healing', 2);
  expect(screen.getByRole('radio', { name: '背包' })).not.toBeDisabled();
  fireEvent.click(screen.getByRole('button', { name: '发送测试邮件' }));
  await waitFor(() =>
    expect(finish).toHaveBeenCalledWith(
      expect.objectContaining({ amount: 2, delivery: 'mail' }),
    ),
  );
});

it('rejects an overlong Chinese title in the actual form', async () => {
  const finish = form('Healing', 1, '中'.repeat(14));
  fireEvent.click(screen.getByRole('button', { name: '发送测试邮件' }));
  await screen.findByText(
    '邮件标题最多 39 个 UTF-8 字节（汉字通常占 3 字节）。',
  );
  expect(finish).not.toHaveBeenCalled();
});

it('accepts exact UTF-8 boundaries and rejects embedded NUL and oversized emoji', () => {
  expect(mailTextError('中'.repeat(13), 39, '标题')).toBe('');
  expect(mailTextError('中'.repeat(166) + 'a', 499, '正文')).toBe('');
  expect(mailTextError('😀'.repeat(10), 39, '标题')).not.toBe('');
  expect(mailTextError('a\0b', 39, '标题')).toBe('标题包含无效字符。');
});
