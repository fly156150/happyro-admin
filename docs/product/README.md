# 产品文档

本目录维护当前能力、权限边界与未完成事项，不重复保存实施顺序和阶段进度。

## 功能文档

- [用户管理](user-management.md)：玩家账号、角色查询和角色维护。
- [游戏资料](game-data.md)：只读查询语义；[导入流水线](game-data-pipeline.md)：来源、图片、存储和导入。
- [运营管理](operations-management.md)：物品发放和运行中世界操作。
- [系统设置](system-settings.md)：资料版本和游戏服务器参数。
- [Game Control](game-control.md)：进程边界、幂等和安全；[命令契约](game-control-commands.md)：payload 与响应。

## 代码职责

后端继续遵循 Laravel 的入口、用例、契约和基础设施分层，并按业务域组织：

```text
backend/app/
├── Contracts/{Auth,Players,GameData,GameServer,Operations}
├── Data/{Auth,Players,GameData,GameServer,Operations}
├── Services/{Auth,Players,GameData,GameServer,Operations,AdventureTools}
├── Infrastructure/{Persistence,GameServer}
└── Http/{Controllers,Requests,Resources}
```

- `Services` 保存可由 HTTP、CLI 和队列复用的业务用例。
- `Contracts` 定义 Repository、游戏服务控制和配置输出端口，所有外部依赖必须可替换、可 Mock。
- `Infrastructure` 保存 Game Control、配置文件与部分持久化适配器；部分已有数据库 Repository 仍在 `Services/` 下，不能把理想分层当作当前目录事实。新增逻辑遵循可注入、可替换的契约。
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

角色维护收敛在 `players/characters` 的详情页面中，不为等级、属性和技能分别创建侧栏菜单。魔物召唤放在 `operations/monster-spawns`，游戏参数放在 `settings/game-settings`。

## 待完善事项

- 角色维护、魔物召唤和设置权限仍可进一步按业务拆分；具体差异见各功能页。
- 技能详情、审计查询、高风险操作原因及配置显式回滚入口仍需完善。
- 真机／在线状态、失败回滚、幂等与真实消耗需针对最终部署验收；旧测试数量不证明当前版本通过。
- 密钥轮换与跨主机控制通道单独设计和验证；基础 Docker 密钥生成与注入已实现。
