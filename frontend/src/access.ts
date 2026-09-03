/**
 * @see https://umijs.org/docs/max/access#access
 * */
export default function access(
  initialState: { currentUser?: API.CurrentUser } | undefined,
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
    canViewPlayers:
      permissions.includes('*') || permissions.includes('players.view'),
  };
}
