# Game Control 命令契约

[通道、权限与幂等](game-control.md)

## 命令契约

所有命令请求都必须带 `id`、`type`、`target` 和 `payload`。`target.type` 只允许 `character` 或 `server`：角色命令使用 `character` 和 `char_id`；不需要角色目标的服务配置命令使用 `server` 和固定的 `target.id = primary`。

### 角色成长

`character.progression.update` 的 payload 只允许以下字段，字段未提供时保持不变：

```json
{"base_level": 99, "job_level": 50, "job_id": 4054}
```

服务端必须按现有升级和转职规则校验上下限、职业组合和前置条件，并返回更新后的等级、职业和剩余点数。禁止把任意 `SP_*` 编号直接暴露给客户端。

### 角色属性

`character.stats.update` 的 payload 使用明确名称和值：

```json
{"str": 90, "agi": 80, "vit": 70, "int": 60, "dex": 50, "luk": 40}
```

属性值和特质值必须分别建模并限制在服务端允许范围内；修改完成后由 map-server 重算派生状态并返回实际值。`character.stats.reset` 不接受属性值，避免“重置后再偷偷写入”的复合操作。

### 技能和状态

- `character.skills.reset` payload 必须为空对象，服务端执行完整技能重置并返回返还点数。
- `character.vitals.restore` payload 可选 `{"vitals": ["hp", "sp", "ap"]}`，缺省表示恢复全部；服务端按上限裁剪。

### 魔物召唤

`monster.spawn` 使用在线角色作为位置锚点：

```json
{"monster_id": 1002, "count": 1, "radius": 3, "duration_seconds": 60}
```

`radius`、`count` 和 `duration_seconds` 由服务端设置硬上限。服务端返回每个实体的 ID、地图名、实际坐标和过期时间；找不到在线角色、地图未加载或没有合法格子时必须明确失败。

### 战斗配置

`battle_config.apply` 只接收注册表键值，不接收文件路径或文本：

```json
{"changes": [{"key": "base_exp_rate", "value": 100}, {"key": "item_rate_common", "value": 100}]}
```

服务端先校验全部键值，任一键失败则整体拒绝并恢复已修改值；成功后返回变更前值和变更后值。Laravel 保存配置版本并回读实际值，失败时恢复配置文件快照。

### 统一响应

成功响应包含 `data.result`，Laravel 命令记录补充命令 ID、状态和时间。失败响应使用稳定错误码，例如 `character_offline`、`invalid_parameter`、`no_spawn_cell`、`unsupported_command`、`configuration_conflict`；错误消息用于展示时必须经过多语言映射，不能把服务端英文原文直接输出到页面。

