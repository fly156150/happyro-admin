import { describe, expect, it } from 'vitest';
import { filterAndSortNpcs, type GameDataNpc } from './npcCatalog';

const npc = (values: Partial<GameDataNpc>): GameDataNpc => ({
  id: 'map:1:1:NPC',
  map: 'map',
  x: 1,
  y: 1,
  name: 'NPC',
  source_name: 'NPC',
  display_name: 'NPC',
  type: 'script',
  enabled: true,
  dynamic: false,
  game_visible: true,
  catalog_order: 0,
  source: { path: 'npc/test.txt', line: 1 },
  ...values,
});

describe('NPC catalog filtering', () => {
  it('defaults to game-visible rows in catalog order', () => {
    const rows = [
      npc({ id: 'second', catalog_order: 2 }),
      npc({ id: 'hidden', game_visible: false, catalog_order: 1 }),
      npc({ id: 'first', catalog_order: 0 }),
    ];

    const result = filterAndSortNpcs(rows, {}, 'game');

    expect(result.map((row) => row.id)).toEqual(['first', 'second']);
  });

  it('includes every catalog row in all mode', () => {
    const rows = [
      npc({ id: 'visible', catalog_order: 1 }),
      npc({ id: 'hidden', game_visible: false, catalog_order: 0 }),
    ];

    const result = filterAndSortNpcs(rows, {}, 'all');

    expect(result.map((row) => row.id)).toEqual(['hidden', 'visible']);
  });

  it('uses the game exact-prefix-contains search ranking', () => {
    const rows = [
      npc({ id: 'contains', display_name: '老卡普拉员工', catalog_order: 0 }),
      npc({ id: 'prefix', display_name: '卡普拉员工', catalog_order: 1 }),
      npc({ id: 'exact', display_name: '卡普拉', catalog_order: 2 }),
    ];

    const result = filterAndSortNpcs(rows, { name_zh_cn: '卡普拉' }, 'game');

    expect(result.map((row) => row.id)).toEqual(['exact', 'prefix', 'contains']);
  });
});
