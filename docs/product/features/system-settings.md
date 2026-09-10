# 系统设置

## 状态

开发中

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
6. 允许从历史版本创建新草稿并重新应用，不直接篡改历史记录。

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
- 第一阶段只开放经验和常用掉率，不建立任意配置文件编辑器。

计划权限拆分为 `settings.game-data.manage`、`settings.game-settings.view` 和 `settings.game-settings.manage`，不继续使用一个覆盖全部设置的宽泛权限。

## 实现记录

- 已完成游戏资料版本设置。
- 已完成 Game Control 命令类型、严格状态流转、幂等提交、执行结果和失败信息的后台持久化基础。
- 已建立 game_server_settings 和 game_server_setting_revisions 表，保存当前值和不可变版本基础。
- 已增加配置版本 Repository 和创建 Service，版本号按服务器在事务中递增并支持依赖注入。
- 已增加 GameServerSettingRegistry，集中定义允许的倍率键、范围、单位和来源文件，并覆盖未知键测试。
- 已增加可注入的配置管理区块写入器，只更新 HAPPYRO ADMIN 管理区块并使用临时文件原子替换。
- 已增加配置准备 Service，在写入前完成注册表校验并创建草稿版本。
- 配置版本 Repository 已限制 draft 只能转换为 applied 或 failed，并记录应用时间。
- 配置写入器已支持读取和原子恢复完整配置快照，为应用失败回滚保留原始内容。
- 已增加配置应用 Service，串联快照、草稿版本、配置写入、Game Control 执行、实际值回读和 applied/failed 状态；失败时恢复旧快照。
- 已增加当前配置 Repository，成功回读后幂等更新 `game_server_settings` 投影，按服务器和配置键保持唯一。
- 已增加当前配置投影的数据库级幂等测试。
- 已增加受保护的 battle_config.read 回读接口，用于确认 map-server 实际运行值。
- Laravel Gateway 已提供 battleConfig() 和 /api/operations/game-control/battle-config，配置页面可读取实际值。
- 命令执行已区分明确失败与结果不确定：网络超时或服务端 5xx 进入 `indeterminate`，不得自动重试产生副作用；使用原命令 ID 和幂等键确认或重试。
- 已增加 `GET/PUT /api/settings/game-settings`，返回服务端实际值、注册表定义并应用倍率变更。
- 已增加 `/settings/game-settings` 页面，参数字段由 API 注册表动态生成，提交时可填写修改备注。
- 配置应用成功必须通过 map-server 回读校验；失败会恢复配置快照并将版本标记为 failed。
- 配置 API 的 FormRequest 会先按同一注册表校验未知键和取值范围，输入错误返回 422，Service 层仍保留二次校验。
- 已增加游戏设置 API 的读取、权限和非法参数 Feature 测试。
- 已增加配置修改记录页面：游戏设置版本展示倍率修改值、备注、操作者及应用状态，游戏资料记录展示客户端与服务端版本变更前后值。

## 待办

- 建立生产密钥生成、注入、轮换和安全传输方案。
- 增加配置版本显式回滚入口。
- 将 `settings.manage` 进一步拆分为游戏资料和游戏设置的查看、管理权限。
