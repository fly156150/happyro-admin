# Game Control

## 状态

当前通道与业务命令已实现；部署密钥生成和注入由根仓库管理，轮换与跨主机方案另行验证。

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

配套部署中 web-server 与 map-server 共享内部 Socket，内部通道使用 Unix Domain Socket，不经过 Nginx，也不复用对外 HTTP 端口。Socket 文件放在运行时目录，权限仅允许 HappyRO 服务账号访问；systemd 负责创建运行目录和清理旧 socket。

服务端仓库提供 `deploy/systemd/happyro-*.service` 物理部署模板。安装后应按 login -> char -> map -> web 顺序启用；map 服务使用 `RuntimeDirectory=happyro` 创建 Socket 目录，web 服务依赖 map 服务但不重复创建目录。模板不会自动启用，安装前必须核对运行用户、绝对路径、数据库服务名和配置文件中的实际 import 链。

map-server 配置键为 `game_control_socket`，默认空值表示关闭。启用时建议配置为 `/run/happyro/map-control.sock`，并由 systemd 的 `RuntimeDirectory` 创建 `/run/happyro`。

### 真实验收前置

真实验收必须使用新构建的 `web-server` 和 `map-server`，并在可控停机窗口完成：

1. 为 web-server 注入不少于 32 字节的 `game_control_secret`，设置 `game_control_enabled: yes` 和同一 Socket 路径。
2. 为 map-server 设置同一 `game_control_socket`，确认运行账号对 Socket 目录有读写权限。
3. 重启两个服务后先请求能力发现和倍率回读，再用测试角色验证等级、属性、技能重置、状态恢复和附近召唤。
4. 验证命令状态、幂等重放、离线目标拒绝、非法参数拒绝和召唤自动清理；最后检查服务日志不包含密钥和完整敏感 payload。
5. 任一项失败立即关闭 Game Control、删除运行时 Socket，并恢复最近一次稳定二进制；不得在旧进程上以 `404` 或空能力列表作为新协议验收结论。

配置按 import 读取顺序覆盖，必须核对最终监听地址、Socket 和密钥。rAthena 将工作目录切换到二进制所在目录，物理部署时二进制与 conf、db 必须属于同一运行根。Admin 使用配套数据库迁移与共享配置权限，见[部署](../deployment.md)。

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

命令类型由服务端白名单维护，以下为常用示例，并非完整能力列表：

- `character.progression.update`
- `character.stats.update`
- `character.stats.reset`
- `character.skills.reset`
- `character.vitals.restore`
- `monster.spawn`
- `battle_config.apply`

完整声明见 `backend/app/Data/GameServer/GameServerCommandType.php`，当前还包含角色快照、导航、属性／特性点、学习技能、骑乘、职业外观、背包与 Zeny 发放。不同入口使用各自白名单和权限；不能将声明的全部类型视为所有用户均可调用。能力发现只返回当前服务端已实现类型，最终资格仍由服务端校验。

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

## 命令参数

各业务 payload、目标与响应见[命令契约](game-control-commands.md)。

## 安全

- 控制入口默认关闭，只在受控内部网络监听，并要求至少 32 字节的运行时密钥。原生部署通常使用 loopback HTTP，Docker 使用内部服务网络；若跨主机部署，必须在受信任的 TLS 终止层后再开放，不能直接暴露 web-server 端口。
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
