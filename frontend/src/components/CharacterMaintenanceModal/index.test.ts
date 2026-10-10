import { describe, expect, it } from 'vitest';
import {
  changedCharacterValues,
  characterFormValues,
  commandTypes,
  fieldMaximum,
  fieldMinimum,
  jobChangePayload,
  mergeCharacterResult,
} from './form-values';

const character = {
  base_level: 99,
  job_level: 50,
  job_id: 7,
  str: 11,
  agi: 22,
  vit: 33,
  int: 44,
  dex: 55,
  luk: 66,
};

describe('characterFormValues', () => {
  it('clamps levels when changing from a fourth job to a lower-cap job', () => {
    expect(
      jobChangePayload(9, {
        base_level: 275,
        job_level: 60,
        jobs: [{ id: 9, max_base_level: 99, max_job_level: 50 }],
      }),
    ).toEqual({ job_id: 9, base_level: 99, job_level: 50 });
  });
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
    expect(
      characterFormValues('points', { status_points: 0, skill_points: 0 }),
    ).toEqual({
      status_points: 0,
      skill_points: 0,
    });
  });

  it('uses the shared points command and live limits for both kinds of points', () => {
    const snapshot = {
      status_points: 48,
      skill_points: 10,
      max_status_points: 2147483647,
      max_skill_points: 32767,
    };
    expect(commandTypes.points).toBe('character.points.update');
    expect(fieldMinimum('points')).toBe(0);
    expect(fieldMaximum('points', 'status_points', snapshot)).toBe(2147483647);
    expect(fieldMaximum('points', 'skill_points', snapshot)).toBe(32767);
    expect(
      changedCharacterValues(
        'points',
        { status_points: 0, skill_points: 10, str: 99 },
        snapshot,
      ),
    ).toEqual({ status_points: 0 });
    expect(
      changedCharacterValues(
        'points',
        { status_points: 48, skill_points: 10 },
        snapshot,
      ),
    ).toEqual({});
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
  });

  it('uses live job limits and nested trait values instead of database defaults', () => {
    const snapshot = {
      ...character,
      max_base_level: 250,
      max_job_level: 50,
      max_stats: { str: 130 },
      traits: {
        enabled: true,
        values: { pow: 12, sta: 0, wis: 0, spl: 0, con: 0, crt: 0 },
        maximums: { pow: 100 },
        points: 10,
        budget: 22,
      },
    };
    expect(characterFormValues('traits', snapshot)).toEqual(
      snapshot.traits.values,
    );
    expect(fieldMaximum('stats', 'str', snapshot)).toBe(130);
    expect(fieldMaximum('traits', 'pow', snapshot)).toBe(100);
    expect(fieldMaximum('progression', 'base_level', snapshot)).toBe(250);
  });
});
