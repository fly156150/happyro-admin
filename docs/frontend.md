# 前端开发

Ant Design Pro 应用位于 `frontend/`，实际依赖以 package.json 和锁文件为准。使用 Node.js 22 或更高版本，检查规则见[前端 AGENTS](../frontend/AGENTS.md)。

在 frontend 目录执行：

```bash
npm ci
npm run lint
npm run test
npm run build
```

测试使用 Vitest。开发入口 `npm run dev` 和 `npm start` 都关闭 mock；仅在没有服务管理器占用该端口的开发环境手工启动。Linux 本机的 8000 端口由 `happyro-admin-frontend.service` 唯一管理，macOS／Docker 按[部署说明](deployment.md)管理。

`src/services/auth/` 使用 Laravel 认证，权限来自当前用户 permissions；不把上游 mock 登录账号或 `/api/currentUser` 当作 HappyRO 认证协议。业务页面与路由对应，未注册的模板示例不代表产品能力。

页面、Service、国际化和业务映射遵守根 AGENTS。Ant Design 接口变更先查询本地 CLI 元数据；生成的 services 与 .umi 不手工维护。超过文件／函数长度限制的静态数据与上游示例须按当前范围解释，启用为产品页面前重审，不沿用历史审计豁免。
