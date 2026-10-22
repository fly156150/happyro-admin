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
