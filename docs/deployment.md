# 部署与升级

## Docker 交付

Admin 是 HappyRO 四组镜像之一，和 Gateway、Server、Database 一起由[根仓库发布流程](https://github.com/happyro/happyro/blob/main/docs/operations/docker-release.md)全量构建。应用版本统一来自根仓库 `deploy/docker/VERSION`。离线包的安装与升级见[公开部署说明](https://happyro.kugarocks.com/container/docker)。

Docker 中 Laravel 通过内部 web-api 服务访问 Game Control，共享配置文件用于倍率等设置的持久化；不要把本机 loopback 地址直接用于跨容器连接。数据库、图片资源及配置挂载由根仓库模板定义。

## 原生开发环境

Linux 模板位于 `deploy/systemd/`；安装前核对 User、Group、绝对路径和监听地址。模板后端监听 `0.0.0.0:18081`，前端监听 8000；根据实际访问边界限制网络入口。macOS 混合环境由根仓库的 launchd 服务脚本管理。

Laravel 经 HTTP 调用 web-server，不直接连接 map-server Socket；共享组权限用于读写受控服务配置。Socket 连接由 web-server 负责。不要根据模板推断当前机器已安装同名服务，验收步骤见根 AGENTS。

## 配置与更新

- 管理库与玩家库分离，核对 backend 的数据库连接、Session／Sanctum 域和前端代理；HTTPS、HTTP Cookie 配置与游戏「保持登录」不是同一套设置。
- `GAME_CONTROL_BASE_URL`、`GAME_CONTROL_TOKEN` 和 `GAME_SERVER_BATTLE_CONFIG_PATH` 在 `config/happyro.php` 读取；保持与服务端密钥、实际共享文件位置一致。
- Docker 初始化已生成并注入 Game Control 密钥；不再将基础密钥生成列为未实现。轮换或跨主机传输需要按部署环境验证，不把已有本机连接当作跨主机方案。
- 更新前备份数据库与运行配置，迁移在正确环境执行 `php artisan migrate --force`；Docker 由配套入口执行迁移，原生部署须自行完成。
- 资料生成后按实际改动运行 `game-data:import-items --all`、`game-data:import-monsters --renewal`、`game-data:import-npcs --renewal`，命令会写管理库。
- 更新后核对健康检查、管理员登录、资料查询、能力发现与配置回读；测试角色操作和有消耗业务单独验收。

不在此维护特定机器的账号、旧包目录、测试角色 ID 或“当前进程已升级”记录。

### 登录页初始凭据提示

`SHOW_DEFAULT_CREDENTIALS=true` 显示“账号密码：admin / admin”，默认关闭；设置 `false` 隐藏。开关仅控制文案，不修改实际账号或密码。Docker 部署在部署目录 `.env` 修改后运行 `docker compose up -d --no-deps --force-recreate admin`，无需重新构建镜像。直接部署 Laravel 时修改后清除或重新生成配置缓存。

### 初始管理员账号

部署目录 `.env` 中的 `ADMIN_INITIAL_USERNAME` 和 `ADMIN_INITIAL_PASSWORD` 指定初始管理员，默认均为 `admin`。首次初始化只在管理库没有任何账号时创建超级管理员；已有账号时直接跳过，不覆盖密码、角色或状态。修改变量不会重置已有账号。用户名沿用小写字母、数字、下划线、点及短横线规则，密码为 5–255 个字符。

使用自定义账号或密码时，登录页隐藏默认凭据提示，即使 `SHOW_DEFAULT_CREDENTIALS=true`；公开配置接口不返回自定义凭据。直接部署使用 `php artisan gm:user:create --initial --no-interaction --no-color`。

提示是固定的 `admin/admin` 文案，不读取真实密码。初始化配置仍为默认值但实际密码已经修改时，应设置 `SHOW_DEFAULT_CREDENTIALS=false` 隐藏提示。
