# 游戏资源

## 状态

开发中

## 第一版

- `物品图鉴`：从本地 Renewal 物品快照查询物品 ID、名称、AegisName、类型、价格和服务器脚本。
- `发放物品`：选择角色和物品，通过游戏邮件发送物品附件。
- 物品名称使用资源快照中的通用 `names` 多语言结构，界面文案使用国际化资源；物品快照来源记录在资源文件中。

## 数据来源

- 服务器属性来自 HappyRO Server `db/re/item_db_*.yml`。
- 客户端 `itemInfo_true.json` 是从 Lua 5.1 字节码离线提取的规范化快照；说明和资源名通过离线索引写入 `descriptions.json`、`icon-map.json`。
- 原始 kRO 资源来自 `inputs/runtime/kro-20211105/client/data.grf`，全量解压结果位于被 Git 忽略的 `work/grf-extract/kro-20211105/data/`。
- 解压清单 `manifest.json` 保存原始路径、规范化路径、文件大小和 SHA-256；提取工具位于 `repos/happyro-gateway/tools/extract-grf.mjs`。
- 管理后台通过可配置的 `GAME_RESOURCE_ROOT` 读取本地资源，默认指向上述解压目录，不把大体积 GRF 或全量解压文件提交到 Git。

## 资源加载

- 列表图标使用 `texture/유저인터페이스/item/<资源名>.bmp`，详情插图使用 `texture/유저인터페이스/collection/<资源名>.bmp`。
- 当前 Laravel `ItemCatalog` 根据物品 ID 和官方资源名映射检查本地文件，并通过受 `auth:sanctum` 与 `players.view` 保护的接口返回图片；`manifest.json` 目前用于提取结果核验，后续离线索引构建再使用它生成资源状态。
- `/api/resources/items/{id}/icon` 返回 24×24 游戏图标；`/api/resources/items/{id}/illustration` 返回通常为 75×100 的详情插图。
- 图片响应使用 `Cache-Control: public, max-age=86400`；前端列表固定 48×64 容器，详情固定 75×100，缺失插图时回退到图标或本地占位图。
- 客户端 BMP 说明中的颜色控制码和空白占位行在 Laravel 服务层清理后再返回，避免把 `^777777` 等内部标记显示给管理员。

## 版本模型

- `客户端资源` 与 `服务端版本` 是两个独立的选择项，不合并为一个版本号。
- 客户端资源当前固定为 `kRO 2021-11-05`（内部目录 `kro-20211105`），决定 `itemInfo_true.json`、`data.grf`、图片和客户端说明的来源。
- 服务端版本来自 HappyRO Server 的 rAthena 数据快照，当前仓库快照为 `251fa0ff`（2026-08-28）；界面显示短 hash 和日期，完整 hash 保存在资源索引元数据中。
- 图鉴查询请求应同时携带两个版本选择，例如：`客户端资源 [kRO 2021-11-05]`、`服务端版本 [rAthena 251fa0ff · 2026-08-28]`。

## 数据范围

图鉴提供三个互斥的数据范围选项，内部值固定为 `client`、`server`、`all`：

- `仅客户端`：只显示客户端 `itemInfo_true.json` 快照中的物品 ID；这不是扫描 GRF 中所有图片文件。
- `仅服务端`：只显示 rAthena `item_db_*.yml` 中的物品 ID。
- `全部`：按物品 ID 对客户端和服务端集合取并集。

默认范围为 `仅客户端`，发放物品使用服务端物品集合并单独校验服务端可发放属性。

## 数据合并规则

- `全部` 范围按物品 ID 去重，每个 ID 只返回一条记录，并保留 `source`：`client`、`server` 或 `both`。
- 客户端字段包括显示名称、说明、资源名、图标和详情插图；服务端字段包括 `AegisName`、服务端名称、类型、价格、攻击、槽位、限制和脚本。
- 同一 ID 的客户端名称与服务端名称都保留；有客户端名称时默认显示客户端名称，没有时回退到服务端名称。两边的说明不互相伪造。
- 物品名称统一使用 `names` 映射，例如 `{"zh-CN":"剑","en-US":"Sword"}`；不再使用 `Name` 或 `NameEn` 字段。
- 仅客户端记录的服务端字段为空，只有服务端记录的客户端字段为空并显示资源缺失状态。数据范围只决定记录是否纳入，不改变字段来源。

## 资源状态

资源状态由离线索引根据客户端快照、官方映射和 `manifest.json` 计算，不能仅依据物品 ID 猜测：

- `complete`：客户端映射和所需 GRF 资源均存在。
- `mapping_missing`：服务端物品存在，但没有对应的客户端 ID 到资源名映射。
- `image_missing`：有客户端映射，但对应图标或详情图片缺失。
- `client_missing`：服务端物品没有客户端快照记录或客户端资源。
- `custom`：明确登记为自定义服务端物品；不能因为资源缺失就自动标记为自定义。

状态标签及缺省文案必须通过国际化资源输出。

## 生产部署

- `data.grf` 保持只读、不修改；完整解压结果放在 `work/grf-extract/`，不提交 Git。
- 生产环境由前置 Nginx 直接提供版本化静态资源，例如 `/game-assets/kro-20211105/data/texture/...`，Laravel 负责物品元数据、说明、资源状态和 URL，不负责图片热路径转发。
- Nginx 对版本化资源设置长期缓存（建议一年并带 `immutable`），禁止目录遍历和暴露整个工作目录；资源目录使用最小只读权限。
- Gateway 是游戏 Web 应用与实时服务的协议适配层，可负责 WebSocket/TCP、rAthena API 代理和资源构建工具；已有 Nginx 时不应让 Gateway 参与静态图片请求。
- 开发环境可以保留 Laravel 图片接口作为回退，生产切换到 Nginx 静态 URL 后，接口仍可用于资源状态检查和诊断。

## 快照生成

- 使用根仓库 `tools/resources/item_catalog/main.py server` 生成后台本地服务端物品快照。
- 使用根仓库 `tools/resources/item_catalog/main.py client` 一次生成仅客户端快照、图标映射和说明索引。
- 服务端工具负责读取 rAthena 数据并生成 Renewal 快照；默认使用服务端英文基线 `2fe6ab3dc4d8`，禁止从已翻译的服务端文件猜测英文名称。
- 仅客户端工具读取 kRO merged 的 `itemInfo_true.json`，用 Renewal 快照中的 `en-US` 名称补齐英文；生成前要求客户端 ID 全部命中服务端快照。
- 两条生成流水线共用分层工程，但客户端和服务端的领域规则互相隔离；完整结构、参数和测试命令见根仓库 `tools/resources/README.md`。

## 验收记录

- GRF 全量提取 `148,805/148,805` 成功，解压后约 `11.4 GiB`。
- 物品 ID `1101` 的说明可正常返回并清理客户端格式码。
- 物品 ID `1107` 的详情插图可解析为 `75×100` BMP。
- 物品 ID `1100` 等没有客户端说明的物品明确显示缺省文案，不使用服务器脚本伪造说明。

## 安全边界

- 发放权限使用 `players.edit`，查询使用 `players.view`。
- 第一版只支持邮件发放，不直接写入 `inventory`，避免在线角色内存状态和物品唯一 ID 冲突。
- 每次发放写入审计事件，后续增加发放记录页面。

## 待办

- 补充其他语言的官方物品说明快照，并按后台当前语言选择说明语言。
- 增加按账号选择角色和物品选择器。
- 增加发放记录查询和重复提交保护。
- 评估通过游戏服务接口支持直接发放背包，而不是直接操作背包表。
