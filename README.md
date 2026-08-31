# HappyRO GM

HappyRO 的游戏管理后台。仓库采用单仓库双应用结构：

- `backend/`：Laravel 13 API
- `frontend/`：Ant Design Pro 6.0.3
- `deploy/systemd/`：本机开发部署的 systemd 服务单元

## 本机地址

- 前端：http://10.24.1.1:8000
- Laravel：http://10.24.1.1:18081
- Laravel 健康检查：http://10.24.1.1:18081/api/health

后台使用 Laravel Session 与 Sanctum 的 Cookie 认证，前端不保留登录 Mock。管理账号、
角色、权限和审计记录分别存放在 `happyro_admin` 库的 `users`、`roles`、
`permissions`、`audit_logs` 等无业务前缀表中，与玩家账号和游戏数据隔离。

创建管理账号：

```bash
cd backend
php artisan gm:user:create admin \
  --name="Administrator" \
  --role=super_admin
```

省略 `--password` 时命令会交互式读取密码。直接运行 `php artisan gm:user:create`
只显示带颜色的帮助和常用示例；日志或管道环境可使用 `--no-color`。

认证代码按接口与实现分离：控制器只处理 HTTP，认证、限流、权限、审计和账号创建
通过 Laravel 容器注入。测试可以直接 Mock `app/Contracts` 下的接口，无需访问数据库
或真实认证驱动。

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
