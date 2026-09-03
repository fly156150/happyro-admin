import { PageContainer } from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { Empty } from 'antd';

export default function GameDataPending({
  titleId,
  title,
}: {
  titleId: string;
  title: string;
}) {
  const intl = useIntl();

  return (
    <PageContainer
      title={intl.formatMessage({ id: titleId, defaultMessage: title })}
    >
      <Empty
        description={intl.formatMessage({
          id: 'gameData.pending.description',
          defaultMessage: '对应游戏资料的本地构建和导入流程将在后续实现。',
        })}
      />
    </PageContainer>
  );
}
