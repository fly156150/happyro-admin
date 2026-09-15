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
  image_available: boolean;
  enabled: boolean;
  dynamic: boolean;
  game_visible: boolean;
  catalog_order: number;
  navigation?: { id: number; class: number } | null;
  source: { path: string; line: number };
  image?: string | null;
};
