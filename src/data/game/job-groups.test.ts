import { describe, expect, it } from 'vitest';
import messages from '@/locales/zh-CN/job-options';
import { groupedJobOptions, jobGroup } from './job-groups';
import { offlineJobIds } from './jobs';

describe('adventure job groups', () => {
  it('offers translated expanded jobs in offline drafts without costume or mounted forms', () => {
    const options = groupedJobOptions(
      offlineJobIds.map((id) => ({ id })),
      (id, fallback) => (messages as Record<string, string>)[id] ?? fallback,
    ).flatMap((group) => group.options);
    for (const [value, label] of [
      [4211, '影狼'],
      [4212, '胧'],
      [4215, '反叛者'],
      [4239, '星帝'],
      [4240, '猎灵士'],
    ] as const) {
      expect(options).toContainEqual({ value, label });
    }
    expect(
      options.some((option) => [13, 22, 26, 4080].includes(option.value)),
    ).toBe(false);
  });
  it('classifies ordinary jobs, novice and expanded jobs using client properties', () => {
    expect(jobGroup(0)).toBe('novice');
    expect(jobGroup(1)).toBe('first');
    expect(jobGroup(7)).toBe('second');
    expect(jobGroup(4054)).toBe('third');
    expect(jobGroup(4218)).toBe('expanded');
    expect(jobGroup(99999)).toBe('other');
  });
  it('gives live trait-capable jobs the fourth-job classification used by adventure tools', () => {
    expect(jobGroup(4304, true)).toBe('fourth');
  });
  it('groups only supplied jobs and displays names without an appended ID', () => {
    const groups = groupedJobOptions(
      [{ id: 7 }, { id: 2 }, { id: 1 }],
      (id, fallback) => (id === 'players.job.knight' ? '骑士' : fallback),
    );
    expect(groups.map((group) => group.label)).toEqual(['first', 'second']);
    expect(groups[0].options.map((option) => option.value)).toEqual([1, 2]);
    expect(groups[1].options[0].label).not.toContain(' · ');
  });
});
