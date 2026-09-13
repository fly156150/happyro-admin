import { BulbOutlined, MoonOutlined } from '@ant-design/icons';
import { useModel } from '@umijs/max';
import { Button } from 'antd';

export default function ThemeToggle() {
  const { initialState, setInitialState } = useModel('@@initialState');
  const dark = initialState?.settings?.navTheme === 'realDark';
  return (
    <Button
      type="text"
      aria-label={dark ? '切换亮色主题' : '切换暗色主题'}
      icon={dark ? <BulbOutlined /> : <MoonOutlined />}
      onClick={() =>
        setInitialState((state) => ({
          ...state,
          settings: {
            ...state?.settings,
            navTheme: dark ? 'light' : 'realDark',
          },
        }))
      }
    />
  );
}
