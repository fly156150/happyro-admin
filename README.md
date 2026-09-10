# HappyRO Admin

HappyRO 的游戏管理后台。单仓库双应用：

- `backend/`：Laravel 13 API
- `frontend/`：Ant Design Pro 6.0.3
- `deploy/systemd/`：本机 systemd 单元

子应用仍保留各自 README 作为上游框架入口。本文件是 HappyRO 整体导航。

## 文档

- [架构](docs/architecture.md)
- [后端](docs/backend.md)
- [前端](docs/frontend.md)
- [部署](docs/deployment.md)
- [产品](docs/product/README.md)

## 本机地址

- 前端：http://10.24.1.1:8000
- Laravel：http://10.24.1.1:18081
- 健康检查：http://10.24.1.1:18081/api/health

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

cd backend && php artisan test
cd frontend && npm run lint && npm run test && npm run build
```

此应用不使用 Docker。Laravel 使用现有 HappyRO MariaDB 实例中的 `happyro_admin` 库。
