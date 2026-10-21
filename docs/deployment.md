# 管理前端部署

在仓库根目录执行 `npm ci` 和 `npm run build`，产物位于 `dist/`。

当前整体部署将前端产物与独立 happyro-api 一起交付，由 API 提供同源静态文件和 HTTP 接口。前端仓库不包含 Go 服务或数据库迁移。

开发模式使用 `npm start`，代理配置见 `config/proxy.ts`。Linux 开发服务模板见 `deploy/systemd/happyro-admin-frontend.service`，安装前核对路径、用户和端口。

API 初始化、环境配置和服务安装见 [happyro-api 部署说明](https://github.com/happyro/happyro-api/blob/main/docs/deployment.md)；整体 Docker 构建与离线交付由根仓库维护。
