# 产品文档

本目录记录 HappyRO Admin 的产品规划、功能设计和项目进度。

## 文档结构

- `progress.md`：项目总体进度。
- `features/`：单个功能的设计、实现记录和待办事项。

每个功能使用一个 Markdown 文件，保持设计和实现记录在同一处。

## 功能文档

- [用户管理](features/user-management.md)：玩家账号、角色查询和角色维护。
- [游戏资料](features/game-data.md)：物品、魔物、NPC 和地图等只读资料。
- [运营管理](features/operations-management.md)：物品发放和运行中世界操作。
- [系统设置](features/system-settings.md)：资料版本和游戏服务器参数。
- [Game Control](features/game-control.md)：后台命令协议、进程边界、幂等和安全约束。

## 目标代码结构

后端继续遵循 Laravel 的入口、用例、契约和基础设施分层，并按业务域组织：

```text
backend/app/
├── Contracts/{Players,GameData,GameServer,GameSettings,Operations}
├── Data/{Players,GameData,GameSettings,Operations}
├── Services/{Players,GameData,GameSettings,Operations}
├── Infrastructure/{Persistence,GameServer,Configuration}
└── Http/{Controllers,Requests,Resources}
```

- `Services` 保存可由 HTTP、CLI 和队列复用的业务用例。
- `Contracts` 定义 Repository、游戏服务控制和配置输出端口，所有外部依赖必须可替换、可 Mock。
- `Infrastructure` 保存 MariaDB、rAthena Game Control 和文件配置实现，不把基础设施细节写进 Service。
- 控制器只负责认证授权、请求转换和响应转换。

Game Control 的 HTTP 接入点只负责认证、能力发现和命令转发；角色状态与地图实体的最终读写必须在 map-server 线程中执行。web-server 不能直接模拟 map-server 内存状态，也不能通过 SQL 代替运行时命令。

前端目录与一级菜单一致：

```text
frontend/src/pages/
├── game-data/
├── players/
├── operations/
└── settings/
```

角色维护收敛在 `players/characters` 的详情页面中，不为等级、属性和技能分别创建侧栏菜单。魔物召唤放在 `operations/monster-spawns`，游戏参数放在 `settings/game-rules`。
