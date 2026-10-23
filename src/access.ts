import type { CurrentUser } from '@/api/auth/types';
/**
 * @see https://umijs.org/docs/max/access#access
 * */
export default function access(
  initialState: { currentUser?: CurrentUser } | undefined,
) {
  const { currentUser } = initialState ?? {};
  const permissions = currentUser?.permissions ?? [];
  return {
    canAdmin: currentUser && currentUser.access === 'admin',
    canGrantItems:
      permissions.includes('*') ||
      permissions.includes('operations.item-grant'),
    canManageSettings:
      permissions.includes('*') || permissions.includes('settings.manage'),
    canGameControl:
      permissions.includes('*') ||
      permissions.includes('operations.game-control'),
    canManageGM:
      permissions.includes('*') ||
      (permissions.includes('players.view') &&
        permissions.includes('players.gm')),
    canEditPlayers:
      permissions.includes('*') ||
      (permissions.includes('players.view') &&
        permissions.includes('players.edit')),
    canViewGameData:
      permissions.includes('*') || permissions.includes('game-data.view'),
    canViewPlayers:
      permissions.includes('*') || permissions.includes('players.view'),
  };
}
