# HappyRO GM

HappyRO 的游戏管理后台。仓库采用单仓库双应用结构：

- `backend/`：Laravel 13 API
- `frontend/`：Ant Design Pro 6.0.3
- `deploy/systemd/`：本机开发部署的 systemd 服务单元

## 本机地址

- 前端：http://10.24.1.1:8000
- Laravel：http://10.24.1.1:18081
- Laravel 健康检查：http://10.24.1.1:18081/api/health

当前前端保留 Ant Design Pro 的本地 mock 登录，仅用于初始界面验证。默认账号为
`admin`，密码为 `ant.design`。正式接入前不得将它视为真实 GM 身份认证。

## 常用命令

```bash
systemctl status happyro-admin-backend happyro-admin-frontend
journalctl -u happyro-admin-backend -f
journalctl -u happyro-admin-frontend -f
systemctl restart happyro-admin-backend happyro-admin-frontend
```

手动运行后端测试和前端检查：

```bash
cd backend && php artisan test
cd frontend && npm run lint && npm run test && npm run build
```

此 GM 应用不使用 Docker。Laravel 使用现有 HappyRO MariaDB 实例中的独立
`happyro_admin` 数据库；现有 `happyro` 和 `happyro_log` 游戏库不承载 Laravel
框架表。游戏数据访问权限应在权限与审计模型确定后再配置。
