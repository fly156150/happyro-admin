# 产品文档

本目录维护当前能力、权限边界与未完成事项，不重复保存实施顺序和阶段进度。

## 功能文档

- [用户管理](user-management.md)：玩家账号、角色查询和角色维护。
- [游戏资料](game-data.md)：只读查询语义；[导入流水线](game-data-pipeline.md)：来源、图片、存储和导入。
- [运营管理](operations-management.md)：物品发放和运行中世界操作。
- [系统设置](system-settings.md)：资料版本和游戏服务器参数。
- [Game Control](game-control.md)：进程边界、幂等和安全；[命令契约](game-control-commands.md)：payload 与响应。

## 代码职责

管理前端位于本仓库，后端由独立 happyro-api 提供。HTTP 与 CLI 复用后端领域服务；管理员与玩家入口分别认证，共享账号、角色、资料、奖励和游戏控制服务。

```text
src/
├── pages/       # 路由页面：accounts、characters、catalog、rewards、settings、login
├── features/    # 角色维护、奖励发放与资料业务组件
├── api/         # 按业务域组织的 HTTP 调用和类型
└── data/game/   # 有来源记录的前端游戏映射
```

角色维护位于 characters 详情页，魔物召唤位于 rewards，游戏参数位于 settings。目录名称按业务职责组织，浏览器 URL 由 config/routes.ts 定义。

Game Control 的 HTTP 接入点负责认证、能力发现和命令转发；角色状态与地图实体的最终读写必须在 map-server 线程中执行，不通过 SQL 代替在线状态命令。

## 待完善事项

- 角色维护、魔物召唤和设置权限仍可进一步按业务拆分；具体差异见各功能页。
- 技能详情、审计查询、高风险操作原因及配置显式回滚入口仍需完善。
- 真机／在线状态、失败回滚、幂等与真实消耗需针对最终部署验收；旧测试数量不证明当前版本通过。
- 密钥轮换与跨主机控制通道单独设计和验证；基础 Docker 密钥生成与注入已实现。
