import { PageContainer } from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import RevisionHistory from '../game-settings/RevisionHistory';

export default function GameSettingHistory() {
  const intl = useIntl();
  return (
    <PageContainer
      title={intl.formatMessage({
        id: 'settings.configurationHistory.title',
        defaultMessage: '配置修改记录',
      })}
    >
      <RevisionHistory revision={0} />
    </PageContainer>
  );
}
