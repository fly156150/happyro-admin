# HappyRO Admin

HappyRO 管理前端，使用 React、Umi、Ant Design 和 TanStack Query。后端由独立的 `happyro-api` 提供。

## 开发

要求 Node.js 22 或以上，使用 npm 和 package-lock.json。

```sh
npm ci
npm start
```

## 验证与构建

```sh
npm test
npm run lint
npx antd lint ./src
npm run build
```

构建结果位于 `dist/`。开发代理配置位于 `config/proxy.ts`。管理员会话和权限由 API 校验；前端权限用于菜单和交互控制。

## 目录

- `src/pages/`：页面和路由组装。
- `src/features/`：业务组件与逻辑。
- `src/api/`：请求、接口类型和响应处理。
- `src/components/`：跨业务通用组件。
- `src/data/`：有明确来源的游戏映射。
- `tests/`：测试支持。
- `deploy/`：前端部署配置。

## 文档

[文档索引](docs/README.md) · [前端开发](docs/frontend.md) · [部署](docs/deployment.md) · [游戏设置](docs/product/system-settings.md) · [Go API](https://github.com/happyro/happyro-api)

## 后台修改密码

头像菜单的「修改密码」用于修改自己的后台登录密码，成功后需重新登录。API 环境变量 `ADMIN_PASSWORD_CHANGE_ENABLED` 是唯一开关，默认 `true`。演示环境设置 `false` 并重启 API 后隐藏入口、拒绝改密请求，后台用户无法重新开启；服务器 CLI 改密不受影响。
