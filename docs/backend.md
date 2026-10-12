# 后端开发

Laravel 应用位于 `backend/`，依赖版本以 `composer.json` 和锁文件为准；规则见[仓库 AGENTS](../AGENTS.md)与[后端 AGENTS](../backend/AGENTS.md)。

在 backend 目录执行：

```bash
composer install
php artisan test
php artisan gm:user:create
```

创建管理员命令无参数仅显示帮助；指定用户名和角色后交互输入密码，示例见根 README。安装、迁移及运行环境见[部署](deployment.md)，测试使用隔离环境。

`routes/web.php` 提供管理员 Session／Sanctum 接口，`routes/api.php` 提供健康检查和游戏内冒险工具入口。`config/happyro.php` 定义资料来源、Game Control 和冒险工具权限配置。

根仓库生成器写入 `resources/game-data/`；物品、魔物及 NPC 经导入命令持久化到 MariaDB，查询不重新解析服务端脚本。具体来源和导入入口见[游戏资料](product/game-data.md)。

控制器不直接查询业务表或拼接远程命令；HTTP、CLI 等入口复用可注入用例。编码和验证规范以 AGENTS 为准，不保留旧合规报告作为当前通过证明。
