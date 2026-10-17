# 系统设置

## 状态

当前已提供受控设置的读取、应用、回读及修改记录；开放键以 GameServerSettingRegistry 为准。

## 子菜单

### 游戏资料

- 管理全局客户端资源版本和服务端资料版本。
- 版本设置只决定图鉴和运营功能使用的本地资料，不修改运行中的游戏倍率。

### 游戏参数

- 经验倍率：基础经验和职业经验。
- 掉落倍率：普通物品、恢复品、消耗品、装备、卡片、MVP 和宝箱。
- 常用战斗参数：后续仅从 HappyRO Server 已核验的配置白名单中逐项开放。

界面使用正数倍率（支持小数，例如 `1`、`1.5`），应用层统一转换为 rAthena 的整数比例，其中 `100` 表示 `1` 倍。参数名称、默认值、范围、单位、来源配置和生效方式必须在代码中的配置注册表明确声明，不能由前端传入任意配置键。

## 应用流程

游戏参数采用“编辑草稿、校验、应用、回读、回滚”的流程：

1. 将期望值和版本保存在 `happyro_admin` MariaDB。
2. 根据受控配置注册表生成 `conf/import/battle_conf.txt` 的管理区块。
3. 校验完整配置后原子替换管理区块。
4. 通过私有 Game Control API 请求 map-server 在主线程调用白名单 `battle_set_value` 更新运行时参数。
5. 回读实际值并记录应用状态；失败时保留旧的已应用版本。
6. 历史版本保留用于查询；显式从历史版本回滚的产品入口尚未实现，见下方待办。

不得直接覆盖 HappyRO Server 的 `conf/battle/*.conf` 基线文件，也不得只修改数据库而不生成可供重启恢复的服务端配置。部署或 map-server 重启后必须继续加载最后一次成功应用的版本。

## 数据设计

- `game_server_settings`：服务器、配置键、期望值、实际值和应用状态。
- `game_server_setting_revisions`：不可变的配置版本、修改备注、操作者和应用结果。
- `game_server_commands`：已实现，保存角色维护、世界操作和配置应用的命令、幂等键、状态与结果。
- 现有 `audit_logs`：记录管理员行为摘要和关联的命令或配置版本。

配置表只保存受注册表约束的键值，不作为任意 rAthena 配置编辑器。命令表包含全局幂等键唯一约束、状态与创建时间组合索引、目标与创建时间组合索引；配置表包含服务器与配置键唯一约束、配置版本与创建时间索引。

## 实现方案

- `PrepareGameServerSettingsService` 负责校验、创建草稿和写入配置，`ApplyGameServerSettingsService` 负责请求应用、回读及失败恢复。
- 文件生成、MariaDB 持久化和游戏服务通信分别通过 Contract 注入，实现可 Mock 和独立测试。
- Game Control 当前由 loopback HTTP 转发到同机 Unix Socket；跨主机生产部署必须增加受信任的 TLS 终止和网络访问控制。
- HTTP 控制器、CLI 命令和队列任务只负责输入转换并调用相同 Service。
- 配置来源以 HappyRO Server 的 `conf/battle/exp.conf`、`conf/battle/drops.conf` 和 `conf/import/battle_conf.txt` 为准，后台保留参数注册表副本并记录来源。
- 开放项包括经验、掉率及已登记的地图传送／召唤等规则，以注册表为准，不建立任意配置文件编辑器。

权限细分仍是计划，当前实际使用 `settings.manage`；不要按拟定权限配置生产账号。

## 当前接口与权限

`GET/PUT /api/settings/game-settings` 读取及应用设置，`GET /api/settings/game-settings/history` 查询修改记录；资料版本与历史使用 game-data 路由。当前均由 `settings.manage` 保护，细分权限仍属待办。

请求层与 Service 同时校验注册表键和范围。命令结果不确定时保留 indeterminate，不自动重试产生副作用；配置应用成功需回读 map-server，失败恢复文件快照并标记失败。

## 待办

- 完善密钥轮换与跨主机传输验收；现有生成和注入见[部署](../deployment.md)。
- 增加配置版本显式回滚入口。
- 将 `settings.manage` 进一步拆分为游戏资料和游戏设置的查看、管理权限。
