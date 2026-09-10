# 部署

systemd 模板：

- `deploy/systemd/happyro-admin-backend.service`：`php artisan serve --host=0.0.0.0 --port=18081`
- `deploy/systemd/happyro-admin-frontend.service`：前端 `8000`

后端用户需要加入 `happyro` 组，才能访问 Game Control Socket。验收步骤见仓库根 `AGENTS.md` 的“本机前端部署与验收”。

此应用不打包进 HappyRO 的三个 Docker 镜像。
