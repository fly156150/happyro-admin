export default [
  {
    path: '/welcome',
    name: 'welcome',
    icon: 'home',
    component: './Welcome',
  },
  {
    path: '/game-data',
    name: 'gameData',
    icon: 'database',
    routes: [
      {
        path: '/game-data/items',
        name: 'items',
        component: './game-data/items',
      },
      {
        path: '/game-data/monsters',
        name: 'monsters',
        component: './game-data/monsters',
      },
      { path: '/game-data/npcs', name: 'npcs', component: './game-data/npcs' },
      { path: '/game-data/maps', name: 'maps', component: './game-data/maps' },
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
        component: './operations/item-grants',
      },
      {
        path: '/operations/zeny-grants',
        name: 'zenyGrants',
        access: 'canGrantItems',
        component: './operations/zeny-grants',
      },
      {
        path: '/operations/item-grant-records',
        name: 'itemGrantRecords',
        access: 'canGrantItems',
        component: './operations/item-grant-records',
      },
    ],
  },
  {
    path: '/players',
    name: 'players',
    icon: 'team',
    routes: [
      {
        path: '/players/accounts',
        name: 'account',
        component: './players/accounts',
      },
      {
        path: '/players/characters',
        name: 'character',
        component: './players/characters',
      },
      {
        path: '/players/login-logs',
        name: 'loginLog',
        component: './players/login-logs',
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
        component: './user/login',
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
