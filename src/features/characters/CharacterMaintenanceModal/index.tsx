import { EditOutlined } from '@ant-design/icons';
import { Button, Modal, Skeleton, Tabs } from 'antd';
import { useState } from 'react';
import MaintenancePanel, { type MaintenanceTab } from './MaintenancePanel';
import { useMaintenance } from './useMaintenance';

const tabs: { key: MaintenanceTab; label: string }[] = [
  { key: 'job', label: '职业' },
  { key: 'progression', label: '等级与点数' },
  { key: 'stats', label: '基础属性' },
  { key: 'traits', label: '四转特性' },
  { key: 'skills', label: '技能' },
  { key: 'vitals', label: '状态恢复' },
];

export default function CharacterMaintenanceModal({
  charId,
  characterName,
}: {
  charId: number;
  characterName: string;
}) {
  const editor = useMaintenance(charId);
  const character = editor.character;
  const [tab, setTab] = useState<MaintenanceTab>('job');
  return (
    <>
      <Button
        type="link"
        size="small"
        icon={<EditOutlined />}
        onClick={() => {
          setTab('job');
          void editor.show();
        }}
      >
        角色属性
      </Button>
      <Modal
        title={
          <span style={{ fontWeight: 400 }}>角色属性 · {characterName}</span>
        }
        width={920}
        open={editor.open}
        onCancel={editor.close}
        closable={!editor.busy}
        mask={{ closable: !editor.busy }}
        destroyOnHidden
        styles={{ body: { maxHeight: '70vh', overflowY: 'auto' } }}
        footer={
          <Button disabled={editor.busy} onClick={editor.close}>
            关闭
          </Button>
        }
      >
        {editor.loading || !character ? (
          <Skeleton active paragraph={{ rows: 7 }} />
        ) : (
          <Tabs
            activeKey={tab}
            onChange={(key) => setTab(key as MaintenanceTab)}
            destroyOnHidden
            items={tabs.map((item) => ({
              ...item,
              disabled: editor.busy,
              children: (
                <MaintenancePanel
                  key={`${item.key}-${editor.revision}`}
                  tab={item.key}
                  character={character}
                  busy={editor.busy}
                  onApply={editor.apply}
                />
              ),
            }))}
          />
        )}
      </Modal>
    </>
  );
}
