// Source: happyro-client/src/UI/Components/GameTools/MapCatalogData.js.
export const commonMapOrder = [
  'prontera',
  'prt_fild08',
  'izlude',
  'geffen',
  'payon',
  'morocc',
  'alberta',
  'aldebaran',
  'yuno',
  'lighthalzen',
  'einbroch',
  'hugel',
  'rachel',
  'veins',
  'comodo',
  'umbala',
  'amatsu',
  'gonryun',
  'louyang',
  'ayothaya',
  'moc_para01',
];

export function compareMaps(
  a: { map: string; name_zh_cn?: string },
  b: { map: string; name_zh_cn?: string },
) {
  const rank = (map: string) => {
    const index = commonMapOrder.indexOf(map);
    return index < 0 ? commonMapOrder.length : index;
  };
  return (
    rank(a.map) - rank(b.map) ||
    (a.name_zh_cn || a.map).localeCompare(b.name_zh_cn || b.map, 'zh-CN') ||
    a.map.localeCompare(b.map)
  );
}
