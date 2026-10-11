# HappyRO Admin

HappyRO 的游戏管理后台。单仓库双应用：

- `backend/`：Laravel 13 API
- `frontend/`：Ant Design Pro 6.0.3
- `deploy/systemd/`：本机 systemd 单元

本文件是 HappyRO Admin 的入口；子应用 README 指向统一维护文档。

## 文档

- [文档索引](docs/README.md)
- [架构](docs/architecture.md)
- [后端](docs/backend.md)
- [前端](docs/frontend.md)
- [部署](docs/deployment.md)
- [产品](docs/product/README.md)

## 本机开发地址（以实际配置为准）

- 前端：http://127.0.0.1:8000
- Laravel：http://127.0.0.1:18081
- 健康检查：http://127.0.0.1:18081/api/health

后台使用 Laravel Session 与 Sanctum Cookie 认证。管理账号存放在 `happyro_admin` 库，与玩家游戏库隔离。

```bash
cd backend
php artisan gm:user:create admin \
  --name="Administrator" \
  --role=super_admin
```

省略 `--password` 时交互式读取密码。无参数运行该命令只显示帮助。

## 常用命令

```bash
systemctl status happyro-admin-backend happyro-admin-frontend
journalctl -u happyro-admin-backend -f
systemctl restart happyro-admin-backend happyro-admin-frontend

(cd backend && php artisan test)
(cd frontend && npm run lint && npm run test && npm run build)
```

Admin 支持 Docker 离线交付与原生开发部署；Laravel 使用独立的 `happyro_admin` 管理库。具体端口、迁移与配置见[部署](docs/deployment.md)。
