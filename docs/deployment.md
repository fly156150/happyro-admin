# 管理前端部署

在仓库根目录执行 `npm ci` 和 `npm run build`，产物位于 `dist/`。

当前整体部署将前端产物与独立 happyro-api 一起交付，由 API 提供同源静态文件和 HTTP 接口。前端仓库不包含 Go 服务或数据库迁移。

开发模式使用 `npm start`，代理配置见 `config/proxy.ts`。Linux 开发服务模板见 `deploy/systemd/happyro-admin-frontend.service`，安装前核对路径、用户和端口。

API 初始化、环境配置和服务安装见 [happyro-api 部署说明](https://github.com/happyro/happyro-api/blob/main/docs/deployment.md)；整体 Docker 构建与离线交付由根仓库维护。

## 后台自助改密

头像菜单的「修改密码」由 API 的 `ADMIN_PASSWORD_CHANGE_ENABLED` 控制，默认 `true`。共享 admin 账号的演示部署设置 `false` 后重启 API，即隐藏入口并拒绝改密请求；后台不提供重新开启的开关。Docker 需重新创建 API 容器以加载环境变量，前端无需重新构建。

改密需校验当前密码及两次新密码，成功后退出当前登录，其他旧会话在下次请求时失效。部署限制不影响服务器 CLI `users password`，也不会改变游戏账号密码。
