# 系统设置

## 页面与权限

“系统设置 → 游戏设置”提供受控参数的读取、修改、实际值回读和修改记录。前端入口为 `src/pages/settings/game-settings/`，接口类型位于 `src/api/settings/game-settings.ts`。访问使用 `settings.manage` 权限。

开放参数以 Go API 的 `internal/settings/registry.go` 和地图服白名单为准，包括 Base / Job 经验、普通 / Mini / MVP 的分类掉落、精炼、导航、召怪和工具权限。后台不是任意 battle 文件编辑器；MVP 奖励物品倍率、宝箱倍率不在当前开放表中。

百分比类参数按倍率显示：界面 `1` 对应服务端 `100`，`1.5` 对应 `150`，有效最小值由注册表决定，不能一律限制为正数。布尔和权限项使用各自枚举，冷却使用秒。

## 配置来源与保存

1. 前端提交变更及幂等键。
2. API 校验权限、键和范围，并读取地图服环境覆盖状态。含环境受控项时，整批请求在写文件前拒绝，错误提示具体变量名。
3. `internal/settings.Service` 通过持久化修订和锁协调文件、命令与运行状态。配置文件位于 `GAME_SERVER_BATTLE_CONFIG_PATH`，只替换受管理区块。
4. API 经 web-server 将 `battle_config.apply` 交给 map-server 主线程；地图服再次检查白名单及环境覆盖，并在任何赋值前拒绝冲突批次。
5. 回读实际值确认后才标记 applied。超时或未知结果保留待确认状态，不自动重发写请求。

部署环境变量优先于文件。被环境变量控制的输入目前仍可编辑，但提交会明确报错；清空对应变量并重启地图服后才能恢复后台管理。完整参数见[环境变量参考](https://happyro.kugarocks.com/deploy/environment-variables)。

## 记录与异常恢复

Go 管理库使用 `settings_revisions` 保存请求、前后配置、状态及地图服版本凭据；`game_server_commands` 保存命令和幂等状态，`audit_logs` 保存操作审计。

`GET/PUT /api/settings/game-settings` 读取及应用设置，`GET /api/settings/game-settings/history` 查询修改记录。记录中的确认／恢复由 `internal/settings.Recovery` 调用同一 Service，依据原命令回执或版本屏障证明旧请求已失效；不是把任意历史版本恢复为当前配置。

文件被外部操作改动时停止应用并要求核对。已发布但未确认的变更需按记录处理，不宣称所有失败都自动回滚；不能仅根据文件存在判断地图服生效。

## 资料版本

“游戏资料”展示目录版本和导入结果。导入由 API 的 `catalog import` 原子发布四份快照，不改变运行中倍率。资料查询语义见[游戏资料](game-data.md)，生成与部署入口见[资料流水线](game-data-pipeline.md)。

## 后续事项

- 显式从任意历史版本回滚的产品入口尚未实现。
- 游戏资料与游戏设置的查看、管理权限尚可进一步拆分，当前使用 `settings.manage`。
- 跨主机控制通道和密钥轮换应独立验收。
