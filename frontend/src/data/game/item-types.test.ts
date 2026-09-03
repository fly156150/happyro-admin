import { describe, expect, it } from 'vitest';
import { ITEM_TYPE_CODES, WEAPON_SUBTYPE_CODES } from './item-types';

describe('item type mappings', () => {
  it('keeps the catch-all item type last', () => {
    expect(ITEM_TYPE_CODES.at(-1)).toBe('Etc');
  });

  it('contains the rAthena weapon subtypes used by filters', () => {
    expect(WEAPON_SUBTYPE_CODES).toContain('1hSword');
    expect(WEAPON_SUBTYPE_CODES).toContain('2hSpear');
    expect(WEAPON_SUBTYPE_CODES).toContain('Revolver');
  });
});
