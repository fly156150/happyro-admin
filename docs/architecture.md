# 架构

Admin 采用 Go API 与 Ant Design Pro 前端，账户、权限、审计与游戏资料位于 `happyro_admin`；玩家查询使用独立 game 连接，不把管理员账号混入玩家 login 表。

```text
浏览器 → Admin 前端／反向代理 → Go / Gin
                               ├── happyro_admin：权限、审计、资料投影、配置版本
                               ├── game / game_log：玩家及登录日志查询
                               └── web-server HTTP → Unix Socket → map-server 主线程
```

版本化 JSON 是导入源；物品、魔物、NPC 导入 MariaDB 后由 Repository 查询。图片通过配置的资源根或对应图片接口提供，不在 HTTP 请求中解析 GRF。地图元数据及资料差异见[游戏资料](product/game-data.md)。

管理员 HTTP 接口使用 SCS 数据库会话和 CSRF 校验；游戏内冒险工具经 Gateway 同源代理，使用独立的游戏会话校验。两种入口共用用例，不共用管理员登录凭据。

控制器负责协议转换，Service 负责用例，Go 接口与 Repository／Gateway 隔离外部依赖；在线角色与世界状态最终由 map-server 主线程修改。目录规范见[产品索引](product/README.md)。
