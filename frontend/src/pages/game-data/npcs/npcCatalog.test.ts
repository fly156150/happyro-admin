import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { filterAndSortNpcs, npcsOnMap, type GameDataNpc } from './npcCatalog';

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

  it('lists game-visible NPCs for one map using the shared catalog filter', () => {
    const rows = [
      npc({ id: 'payon-a', map: 'payon', catalog_order: 1, display_name: '铁匠' }),
      npc({ id: 'prt-a', map: 'prontera', catalog_order: 0, display_name: '卡普拉' }),
      npc({
        id: 'payon-hidden',
        map: 'payon',
        catalog_order: 0,
        display_name: '隐藏',
        game_visible: false,
      }),
      npc({ id: 'payon-b', map: 'PAYON', catalog_order: 2, display_name: '仓库' }),
    ];

    expect(npcsOnMap(rows, 'payon').map((row) => row.display_name)).toEqual([
      '仓库',
      '铁匠',
    ]);
    expect(npcsOnMap(rows, 'PAYON').map((row) => row.id)).toEqual(['payon-b', 'payon-a']);
  });
});

describe('admin map details', () => {
  it('renders the filtered NPC list from the shared catalog helper', () => {
    const source = readFileSync(
      resolve(import.meta.dirname, '../maps/index.tsx'),
      'utf8',
    );
    expect(source).toContain('npcsOnMap(npcs, detail.map)');
    expect(source).toContain("id: 'gameData.map.npcs'");
  });
});
