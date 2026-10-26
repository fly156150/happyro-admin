# 管理前端部署

在仓库根目录执行 `npm ci` 和 `npm run build`，产物位于 `dist/`。

Docker 部署由独立 `happyro-admin` Nginx 镜像提供前端静态文件，并将同源 `/api` 请求代理到 Compose 服务 `api:8080`。前端与 API 镜像随同一版本离线包交付；Go 服务和数据库迁移由 API 镜像负责。

前端 Dockerfile 位于 `deploy/docker/Dockerfile`，在 Node 构建阶段生成 `dist/`，运行阶段以非 root Nginx 监听 8080。根仓库 Compose 将后台宿主机端口映射到此容器，提供只读根文件系统与 `/tmp` 临时挂载；前端健康检查为 `/healthz`，API 健康检查为 `/api/health`。完整代理行为见 [独立容器部署](../README.md#独立容器部署)。

开发模式使用 `npm start`，代理配置见 `config/proxy.ts`。Linux 开发服务模板见 `deploy/systemd/happyro-admin-frontend.service`，安装前核对路径、用户和端口。

API 初始化、环境配置和服务安装见 [happyro-api 部署说明](https://github.com/happyro/happyro-api/blob/main/docs/deployment.md)；整体 Docker 构建与离线交付由根仓库维护。

## 后台自助改密

头像菜单的「修改密码」由 API 的 `ADMIN_PASSWORD_CHANGE_ENABLED` 控制，默认 `true`。共享 admin 账号的演示部署设置 `false` 后重启 API，即隐藏入口并拒绝改密请求；后台不提供重新开启的开关。Docker 需重新创建 API 容器以加载环境变量，前端无需重新构建。

改密需校验当前密码及两次新密码，成功后退出当前登录，其他旧会话在下次请求时失效。部署限制不影响服务器 CLI `users password`，也不会改变游戏账号密码。
