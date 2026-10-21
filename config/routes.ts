export default [
  {
    path: '/welcome',
    name: 'welcome',
    icon: 'home',
    component: './Welcome',
  },
  {
    path: '/game-data',
    access: 'canViewGameData',
    name: 'gameData',
    icon: 'database',
    routes: [
      {
        path: '/game-data/items',
        name: 'items',
        component: './catalog/items',
      },
      {
        path: '/game-data/monsters',
        name: 'monsters',
        component: './catalog/monsters',
      },
      { path: '/game-data/npcs', name: 'npcs', component: './catalog/npcs' },
      { path: '/game-data/maps', name: 'maps', component: './catalog/maps' },
    ],
  },
  {
    path: '/players',
    access: 'canViewPlayers',
    name: 'players',
    icon: 'team',
    routes: [
      {
        path: '/players/accounts',
        name: 'account',
        component: './accounts',
      },
      {
        path: '/players/characters',
        name: 'character',
        component: './characters',
      },
      {
        path: '/players/login-logs',
        name: 'loginLog',
        component: './accounts/login-logs',
      },
    ],
  },
  {
    path: '/operations',
    name: 'operations',
    icon: 'tool',
    routes: [
      {
        path: '/operations/item-grants',
        name: 'itemGrants',
        access: 'canGrantItems',
        component: './rewards/item-grants',
      },
      {
        path: '/operations/zeny-grants',
        name: 'zenyGrants',
        access: 'canGrantItems',
        component: './rewards/zeny-grants',
      },
      {
        path: '/operations/item-grant-records',
        name: 'itemGrantRecords',
        access: 'canGrantItems',
        component: './rewards/item-grant-records',
      },
    ],
  },
  {
    path: '/settings',
    name: 'settings',
    icon: 'setting',
    access: 'canManageSettings',
    routes: [
      {
        path: '/settings/game-settings',
        name: 'gameSettings',
        component: './settings/game-settings',
      },
      {
        path: '/settings/game-setting-history',
        name: 'gameSettingHistory',
        component: './settings/game-setting-history',
      },
    ],
  },
  {
    path: '/user',
    layout: false,
    routes: [
      {
        path: '/user/login',
        name: 'login',
        component: './login',
      },
      {
        path: '/user',
        redirect: '/user/login',
      },
    ],
  },
  {
    path: '/',
    redirect: '/welcome',
  },
  {
    path: '/*',
    redirect: '/welcome',
  },
];
