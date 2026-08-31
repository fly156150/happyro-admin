export default [
  {
    path: '/players',
    name: 'players',
    icon: 'team',
    routes: [
      { path: '/players/accounts', name: 'account', component: './players/accounts' },
      { path: '/players/characters', name: 'character', component: './players/characters' },
      { path: '/players/login-logs', name: 'loginLog', component: './players/login-logs' },
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
    path: '/welcome',
    name: 'welcome',
    icon: 'home',
    component: './Welcome',
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
