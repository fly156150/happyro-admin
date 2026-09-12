// Source: happyro-server db/re/item_db_*.yml Type and item_db_equip.yml SubType values.
export const ITEM_TYPE_CODES = [
  'Healing',
  'Usable',
  'DelayConsume',
  'Armor',
  'Weapon',
  'Card',
  'PetEgg',
  'PetArmor',
  'Ammo',
  'ShadowGear',
  'Cash',
  'Etc',
] as const;

export const WEAPON_SUBTYPE_CODES = [
  'Dagger',
  '1hSword',
  '2hSword',
  '1hSpear',
  '2hSpear',
  '1hAxe',
  '2hAxe',
  'Mace',
  'Staff',
  '2hStaff',
  'Bow',
  'Katar',
  'Book',
  'Knuckle',
  'Musical',
  'Whip',
  'Huuma',
  'Revolver',
  'Rifle',
  'Gatling',
  'Shotgun',
  'Grenade',
] as const;

export const ARMOR_SLOT_CODES = [
  'Head_Top',
  'Head_Mid',
  'Head_Low',
  'Head',
  'Armor',
  'Garment',
  'Shoes',
  'Shield',
  'Accessory',
  'Right_Accessory',
  'Left_Accessory',
  'Costume_Head_Top',
  'Costume_Head_Mid',
  'Costume_Head_Low',
  'Costume_Head',
  'Costume_Garment',
] as const;

export const CARD_SLOT_CODES = [
  ...ARMOR_SLOT_CODES,
  'Weapon',
  'Any',
] as const;

export const CARD_SUBTYPE_CODES = ['Enchant', ...CARD_SLOT_CODES] as const;

export const SUBTYPE_FILTER_TYPES = ['Weapon', 'Armor', 'Card'] as const;
