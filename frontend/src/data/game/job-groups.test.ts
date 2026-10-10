import { describe, expect, it } from 'vitest';
import { groupedJobOptions, jobGroup } from './job-groups';

describe('adventure job groups', () => {
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
      (id, fallback) => id === 'players.job.knight' ? '骑士' : fallback,
    );
    expect(groups.map((group) => group.label)).toEqual(['first', 'second']);
    expect(groups[0].options.map((option) => option.value)).toEqual([1, 2]);
    expect(groups[1].options[0].label).not.toContain(' · ');
  });
});
