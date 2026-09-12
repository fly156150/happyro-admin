import { describe, expect, it } from 'vitest';
import {
  characterFormValues,
  maxMaintainedStat,
  mergeCharacterResult,
} from './form-values';

const character = {
  base_level: 99,
  job_level: 50,
  class: 7,
  str: 11,
  agi: 22,
  vit: 33,
  int: 44,
  dex: 55,
  luk: 66,
};

describe('characterFormValues', () => {
  it('maps current character stats into the stats form', () => {
    expect(characterFormValues('stats', character)).toEqual({
      str: 11,
      agi: 22,
      vit: 33,
      int: 44,
      dex: 55,
      luk: 66,
    });
  });

  it('maps the current class to the progression job field', () => {
    expect(characterFormValues('progression', character)).toEqual({
      base_level: 99,
      job_level: 50,
    });
    expect(characterFormValues('job', character)).toEqual({ job_id: 7 });
  });

  it('returns no values for commands with an empty payload', () => {
    expect(characterFormValues('statsReset', character)).toEqual({});
  });

  it('uses the returned job after a successful job change', () => {
    const updated = mergeCharacterResult(character, { job_id: 4054 });
    expect(characterFormValues('job', updated)).toEqual({ job_id: 4054 });
  });

  it('keeps skill point zero as a valid value', () => {
    expect(characterFormValues('skillPoints', { skill_point: 0 })).toEqual({
      skill_points: 0,
    });
  });

  it('keeps command results as the next form snapshot', () => {
    const updated = mergeCharacterResult(character, { str: 120, agi: 121 });

    expect(characterFormValues('stats', updated)).toEqual({
      str: 120,
      agi: 121,
      vit: 33,
      int: 44,
      dex: 55,
      luk: 66,
    });
    expect(maxMaintainedStat).toBe(32767);
  });
});
