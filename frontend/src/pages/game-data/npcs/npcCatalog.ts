export type NpcVisibility = 'game' | 'all';

export type GameDataNpc = {
  id: string;
  map: string;
  map_name_zh_cn?: string | null;
  x: number;
  y: number;
  name: string;
  source_name: string;
  display_name: string;
  name_zh_cn?: string | null;
  type: string;
  sprite_id?: number | null;
  display_sprite_id?: number | null;
  enabled: boolean;
  dynamic: boolean;
  game_visible: boolean;
  catalog_order: number;
  navigation?: { id: number; class: number } | null;
  source: { path: string; line: number };
  image?: string | null;
};

export type NpcFilters = {
  map?: unknown;
  name?: unknown;
  name_zh_cn?: unknown;
};

const normalize = (value: unknown) => String(value ?? '').trim().toLocaleLowerCase();

function matchRank(row: GameDataNpc, term: string): number {
  const values = [
    row.display_name,
    row.name_zh_cn,
    row.source_name,
    row.name,
    row.map_name_zh_cn,
    row.map,
    row.type,
    row.sprite_id,
  ].map(normalize);
  if (values.some((value) => value === term)) return 0;
  if (values.some((value) => value.startsWith(term))) return 1;
  if (values.some((value) => value.includes(term))) return 2;
  return 3;
}

export function filterAndSortNpcs(
  rows: GameDataNpc[],
  filters: NpcFilters,
  visibility: NpcVisibility,
): GameDataNpc[] {
  const originalName = normalize(filters.name);
  const displayName = normalize(filters.name_zh_cn);
  const map = normalize(filters.map);
  const term = displayName || originalName || map;

  return rows
    .filter(
      (row) =>
        (visibility === 'all' || row.game_visible) &&
        (!originalName || normalize(row.name).includes(originalName)) &&
        (!displayName || normalize(row.display_name).includes(displayName)) &&
        (!map || [row.map, row.map_name_zh_cn].some((value) => normalize(value).includes(map))),
    )
    .sort((left, right) => {
      if (!term) return left.catalog_order - right.catalog_order;
      return (
        matchRank(left, term) - matchRank(right, term) ||
        left.display_name.localeCompare(right.display_name) ||
        (left.map_name_zh_cn || left.map).localeCompare(right.map_name_zh_cn || right.map) ||
        left.catalog_order - right.catalog_order
      );
    });
}
