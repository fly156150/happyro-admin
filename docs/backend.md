# 后端

Laravel 应用位于 `backend/`。创建管理员、运行测试和框架说明见 [backend/README.md](../backend/README.md)；HappyRO 规则以仓库根 `AGENTS.md` 为准。

资料 JSON 由根仓库生成器写入 `backend/resources/game-data/`。NPC 查询读取版本化目录，不再独立解析服务端脚本。

```bash
cd backend
php artisan test
php artisan gm:user:create
```
