# Game Control

## 状态

核心通道与当前业务命令已完成，生产密钥管理待实现

## 目标

为后台提供角色维护、魔物召唤和游戏参数应用能力，同时保证所有运行时状态由 HappyRO Server 的正确进程和主线程负责。

## 进程边界

```text
happyro-admin Laravel
        |
        | loopback HTTP（生产可由前置 TLS 终止），Bearer，幂等键
        v
web-server 控制入口
        |
        | Unix Domain Socket 内部命令通道
        v
map-server 主线程
        |
        +-- 在线角色状态、技能和属性
        +-- 地图实体和魔物
        +-- 运行时 battle_config
```

- web-server 只负责认证、能力发现、命令转发和结果返回，不直接操作 map-server 内存。
- map-server 是角色运行时状态和地图实体的唯一执行者；命令必须排队后在主线程处理。
- char-server 负责需要角色持久化的数据库协调，不能由后台直接修改 `char` 或 `skill` 表代替服务端逻辑。
- 任何进程都不能执行任意 SQL、任意 GM 指令或任意脚本字符串。

## 内部命令通道

生产部署中 web-server 与 map-server 位于同一台主机，内部通道使用 Unix Domain Socket，不经过 Nginx，也不复用对外 HTTP 端口。Socket 文件放在运行时目录，权限仅允许 HappyRO 服务账号访问；systemd 负责创建运行目录和清理旧 socket。

服务端仓库提供 `deploy/systemd/happyro-*.service` 物理部署模板。安装后应按 login -> char -> map -> web 顺序启用；map 服务使用 `RuntimeDirectory=happyro` 创建 Socket 目录，web 服务依赖 map 服务但不重复创建目录。模板不会自动启用，安装前必须核对运行用户、绝对路径、数据库服务名和配置文件中的实际 import 链。

map-server 配置键为 `game_control_socket`，默认空值表示关闭。启用时建议配置为 `/run/happyro/map-control.sock`，并由 systemd 的 `RuntimeDirectory` 创建 `/run/happyro`。

### 真实验收前置

真实验收必须使用新构建的 `web-server` 和 `map-server`，并在可控停机窗口完成：

1. 为 web-server 注入不少于 32 字节的 `game_control_secret`，设置 `game_control_enabled: yes` 和同一 Socket 路径。
2. 为 map-server 设置同一 `game_control_socket`，确认运行账号对 Socket 目录有读写权限。
3. 重启两个服务后先请求能力发现和倍率回读，再用测试角色验证等级、属性、技能重置、状态恢复和附近召唤。
4. 验证命令状态、幂等重放、离线目标拒绝、非法参数拒绝和召唤自动清理；最后检查服务日志不包含密钥和完整敏感 payload。
5. 任一项失败立即关闭 Game Control、删除运行时 Socket，并恢复最近一次稳定二进制；不得在旧进程上以 `404` 或空能力列表作为新协议验收结论。

在测试环境切换完成前，运行中的服务曾是旧二进制且配置关闭；切换后的实际结果见下方“测试环境真实验收”。

### 隔离协议验收

在不影响现有服务的临时目录中使用新构建的 web-server，并将端口改为 `18889`、启用 Game Control、配置测试密钥后，已验证：

- 未携带 Bearer 凭据的能力请求返回 `401 unauthorized`。
- 携带合法凭据但未启动 map-server Socket 的请求返回 `503 map_server_unavailable`。
- 携带合法凭据并由临时 Unix Socket 返回有效 map-server 响应时，能力请求返回 `200`，且保留协议版本和命令列表。
- Game Control 开启但清空 Socket 配置时，web-server 在启动阶段以错误退出，并输出缺少 Socket 路径的诊断。

隔离验收确认了 HTTP 鉴权、协议路由、Unix Socket 成功转发和 map-server 不可用时的错误映射；角色修改、技能重置、状态恢复和魔物召唤另已在下方真实游戏进程验收。

验收时必须同时检查 `conf/web_athena.conf` 及其 `import` 链中的最终值；rAthena 配置按读取顺序覆盖，同名键在后读取的文件中生效。备用实例曾因 `conf/import/web_conf.txt` 覆盖端口而未按主配置启动，这类覆盖必须在启动日志和端口探测中确认。

rAthena 进程会将工作目录切换到可执行文件所在目录，因此物理部署时必须让二进制与 `conf/`、`db/` 等运行时目录保持同一安装根；仅替换二进制而保留另一目录的配置不会生效。

### 测试环境真实验收

测试环境已在保留 login-server 和 char-server 的前提下切换新 map-server 与 web-server，并启用 `/run/happyro/map-control.sock`：

- map-server 成功加载 1265 张地图并连接现有 char-server。
- `GET /game-control/v1/capabilities` 返回 `200`，包含角色维护、技能重置、状态恢复、魔物召唤和倍率修改命令。
- `GET /game-control/v1/battle-config` 返回 `200`，成功回读 8 个白名单倍率的当前值（初始均为 `100`）。
- 使用数据库中离线角色 `char_id=150000` 提交等级修改命令，返回 `409 character_offline`，没有执行离线修改。
- 通过真实浏览器登录后台，在“游戏设置”页面提交不变倍率，后台 API、web-server、map-server 和实际值回读均返回成功，页面显示保存成功。

在线角色操作已使用 Robrowser 测试账号 `autotest` 实际验证。角色 `150001` 连接到当前 map-server 后，等级更新、属性更新、技能重置、生命值恢复和角色附近魔物召唤均返回成功；召唤响应包含实体 ID、地图和实际坐标。另以离线角色验证了上述命令会返回 `character_offline`，不会修改离线数据库记录。启动日志另有若干中文名称超过 rAthena 字段长度的告警，服务会截断名称；这属于资料长度问题，不影响本次 Game Control 通道启动。

测试环境还验证了共享权限要求：Laravel 运行用户能够访问权限为 `0660` 的 Socket，并能原子写入 `conf/import/battle_conf.txt`。物理部署模板由 map-server 使用 `UMask=0007` 创建组可访问 Socket，admin backend 使用 `SupplementaryGroups=happyro` 访问 Socket 和配置文件。未执行新增迁移时页面会因缺少版本表失败，部署必须先执行 admin 仓库的 `php artisan migrate --force`。

- web-server 每个请求只提交一个结构化命令，并等待有限时间获取结果。
- map-server 接收线程只负责解析信封并投递到主线程队列，不能直接触碰角色或地图对象。
- map-server 主线程执行命令、生成结果和审计所需的实际状态，再通过关联命令 ID 返回。
- 通道断开或超时统一映射为 `indeterminate`；不能因为重连而自动重复执行。
- Unix Socket 不作为管理员认证边界；服务账号和文件权限限制连接方，HTTP 层校验命令信封，Laravel 持久化层绑定幂等键与请求指纹。

开发环境可以提供 loopback TCP 适配器以便契约测试，但生产配置不得因为测试方便而回退到公网可达地址。跨主机部署属于独立架构变更，届时必须使用专用 TLS/mTLS 通道并重新审核密钥和网络策略。

## 协议

协议版本固定为 `1`。命令使用结构化 JSON：

```json
{
  "id": "后台命令 UUID",
  "type": "monster.spawn",
  "target": {"type": "character", "id": "角色 ID"},
  "payload": {"monster_id": 1002, "count": 1}
}
```

命令类型由服务端白名单维护，当前协议类型包括：

- `character.progression.update`
- `character.stats.update`
- `character.stats.reset`
- `character.skills.reset`
- `character.vitals.restore`
- `monster.spawn`
- `battle_config.apply`

能力发现只返回已经在当前服务端构建中实现的类型，不能根据后台枚举猜测能力。

## rAthena 能力映射

Game Control 的命令必须映射到 HappyRO Server 已有的领域函数，由 map-server 主线程执行。后台不通过修改在线角色表、执行任意 atcommand 或注入脚本来实现功能。

| 命令 | 服务端复用能力 | 约束 |
| --- | --- | --- |
| `character.progression.update` | `pc_setparam` 的 `SP_BASELEVEL`、`SP_JOBLEVEL`、`SP_CLASS` 等受限参数 | 只允许白名单字段；由服务端校验等级上限、职业合法性及升级副作用；完成后触发状态和客户端同步 |
| `character.stats.update` | `pc_setparam` / `pc_setstat` 及状态重算流程 | 不接受任意参数编号；属性、特质点、生命和魔法值分别校验范围；不得直接更新 `char` 表绕过在线状态 |
| `character.stats.reset` | `pc_resetstate` | 由服务端按当前职业、等级和配置重新计算点数，并同步属性；请求只表达“重置”，不携带数据库结果 |
| `character.skills.reset` | `pc_resetskill` | 使用完整重置语义，保留服务端针对排名角色、坐骑和状态的现有处理 |
| `character.vitals.restore` | `pc_heal` 或等价的服务端恢复流程 | 默认恢复到合法上限，不允许后台伪造超过上限的 HP/SP/AP |
| `monster.spawn` | `mob_once_spawn` 与 `map_search_freecell` | 目标是在线角色时传入该角色作为锚点，使用其附近合法格子；数量、半径和持续时间受服务端上限约束；只创建一次性实体 |
| `battle_config.apply` | 受控配置注册表和 `battle_set_value` 运行时更新流程 | 仅允许注册的经验、掉落倍率键；先校验并生成版本，再由服务端原子应用；禁止传入文件路径或任意配置文本 |

上述函数都依赖 map-server 的在线内存状态或主线程设施，因此不能由 web-server 直接调用。角色不在线时，成长和属性类命令必须明确拒绝，或另行设计“离线角色维护”用例，不能混用在线命令语义。

魔物召唤的“管理员附近”不作为服务端概念保存。后台先选择在线测试角色，服务端读取该角色实时地图和坐标，并在相邻可行走格中选择实际位置；响应必须返回最终地图、坐标和实体 ID，供审计和清理使用。

倍率修改属于服务器运行配置，不写入玩家数据。配置注册表需要包含键名、数据类型、允许范围、默认值、当前版本和回滚版本；经验与掉落修改必须有独立权限、原因和审计记录。

## 状态和幂等

后台命令状态为：

```text
pending -> running -> succeeded
                    -> failed
                    -> indeterminate -> running
```

- `failed` 仅表示服务端明确拒绝或明确失败。
- `indeterminate` 表示请求超时、连接中断或服务端异常，不能确认命令是否已经产生副作用。
- `indeterminate` 命令只能使用原命令 ID 和原幂等键查询或重试，禁止创建一个看似新的副本来绕过保护。
- 幂等键与请求指纹绑定；同一键携带不同目标、类型或参数必须拒绝。
- 已完成命令不可回到 `running`，需要重新执行时必须由业务用例明确创建新的命令。

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

## 安全

- 控制入口默认关闭，只允许 loopback 监听，并要求至少 32 字节的运行时密钥。当前实现使用 loopback HTTP；若跨主机部署，必须在受信任的 TLS 终止层后再开放，不能直接暴露 web-server 端口。
- 密钥只从部署环境注入，不写入仓库、日志、审计 metadata 或异常消息。
- 写请求不自动重试；只有服务端确认支持幂等重放时才允许重试。
- 请求、响应和异常日志不得输出 Authorization、完整 payload 中的秘密或数据库凭据。
- 当前角色维护和魔物召唤共用 `operations.game-control` 权限；生产开放前应收敛为独立业务接口、权限、审计字段和适当的速率限制。

## Laravel API

后台 API 位于 `/api/operations/game-control`，统一使用 `operations.game-control` 权限：

- `GET /capabilities`：读取 map-server 实际能力清单。
- `POST /commands`：校验、幂等提交并执行结构化命令。
- `GET /commands/{commandId}`：读取命令状态、结果或不确定执行状态。

战斗倍率不接受通用运营命令入口提交，只能通过受 `settings.manage` 保护的 `PUT /api/settings/game-settings` 应用。

控制器只负责请求转换和响应，命令构造、持久化、网关通信和状态流转分别由 FormRequest、DTO、Repository、Gateway 和 Service 完成。HTTP、CLI 或后台任务接入新入口时必须复用同一组 Service，不能在控制器中复制游戏业务逻辑。

## 实现顺序（目标与当前状态）

1. 在 map-server 增加 Unix Socket 控制通道，将请求安全地投递到主线程队列。（已完成）
2. 在 map-server 注册命令处理器和能力清单。（已完成）
3. 在 web-server 增加转发和结果查询，能力发现只读实际注册表。（已完成）
4. 实现角色详情只读查询及成长、属性、技能和状态用例。（基础列表和维护入口已完成）
5. 实现基于在线测试角色附近合法格子的魔物一次性召唤。（命令和页面入口已完成）
6. 实现受白名单约束的经验/掉落配置草稿、应用、回读和回滚。（已完成；测试环境真实保存链路已验收）

每一步先完成 Contract、Service、协议测试和失败路径测试，再接入页面。涉及 map-server 的能力必须补充真实运行服务验收。

## 实现记录

- 已完成 Laravel 命令 DTO、状态机、幂等持久化和 HTTP Gateway。
- 已增加命令提交路由 Feature 测试，覆盖 FormRequest、权限、幂等持久化和成功状态转换。
- 已统一魔物召唤持续时间字段为 `duration_seconds`，并在 map-server 对非对象 payload 明确拒绝，避免静默使用默认值。
- map-server 对角色成长、属性、魔物召唤和状态恢复执行严格字段白名单校验，未知字段统一拒绝。
- 已完成 web-server 默认关闭的能力发现端点。
- map-server 已实现 Unix Socket 控制通道和角色、召唤、倍率命令；默认配置仍关闭，测试环境真实通道和在线角色操作均已验收。
