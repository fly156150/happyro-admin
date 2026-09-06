// Source: happyro-server/src/map/map.hpp (e_race/e_element) and
// happyro-server/src/map/mob.hpp (e_size). Values follow the server YAML names.
export const MONSTER_RACES = [
  'Formless',
  'Undead',
  'Brute',
  'Plant',
  'Insect',
  'Fish',
  'Demon',
  'Demihuman',
  'Angel',
  'Dragon',
  'Player_Human',
  'Player_Doram',
] as const;
export const MONSTER_ELEMENTS = [
  'Neutral',
  'Water',
  'Earth',
  'Fire',
  'Wind',
  'Poison',
  'Holy',
  'Dark',
  'Ghost',
  'Undead',
] as const;
export const MONSTER_SIZES = ['Small', 'Medium', 'Large'] as const;
