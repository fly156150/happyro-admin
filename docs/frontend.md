# 前端

Ant Design Pro 应用位于 `frontend/`。上游框架说明见 [frontend/README.md](../frontend/README.md) 与 [frontend/README.zh-CN.md](../frontend/README.zh-CN.md)。

本机 `8000` 必须由 `happyro-admin-frontend.service` 提供。不要用手工 `umi` 或 `root` 后台进程长期占用该端口。改动未生效时先核对监听进程是否属于该服务的 cgroup。

```bash
cd frontend
npm run lint
npm run test
npm run build
```
