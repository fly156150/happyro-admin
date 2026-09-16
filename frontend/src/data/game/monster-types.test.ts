import { describe, expect, it } from 'vitest';
import { MONSTER_KINDS } from './monster-types';

describe('monster kind mappings', () => {
  it('uses the same catalog kinds as adventure tools', () => {
    expect(MONSTER_KINDS).toEqual(['all', 'normal', 'mini', 'mvp']);
  });
});
