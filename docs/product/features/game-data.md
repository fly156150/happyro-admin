# 游戏资料

## 状态

核心图鉴已完成，扩展语言和发放辅助能力继续开发

## 第一版

- `物品图鉴`：按客户端、服务端或两者并集查询物品 ID、双语名称、类型、价格和说明。
- `魔物图鉴`：查询魔物双语名称、等级、生命、种族、属性、体型、首领状态、能力值和掉落，并显示客户端游戏精灵。
- `NPC 查询`：查询脚本 NPC 的地图、坐标、中文名称和客户端形象。
- `地图查询`：查询 991 条服务端地图索引、中文名称和客户端地图图片；缺少真实图片时明确显示“暂无图片”。
- `发放物品` 属于运营写操作，放在“运营管理”中，不属于只读的游戏资料目录。
- 魔物图鉴可以提供“召唤”快捷入口，但召唤由运营管理的统一 Service 和权限执行，图鉴本身保持只读。
- 物品名称使用资源快照中的通用 `names` 多语言结构，界面文案使用国际化资源；物品快照来源记录在资源文件中。

## 数据来源

- 服务器属性来自 HappyRO Server `db/re/item_db_*.yml`。
- 客户端 `itemInfo_true.json` 是从 Lua 5.1 字节码离线提取的规范化快照；说明和资源路径通过离线索引写入 `descriptions.json`、`item-assets.json`。
- 原始 kRO 资源来自 `inputs/runtime/kro-20211105/client/data.grf`，全量解压结果位于被 Git 忽略的 `work/grf-extract/kro-20211105/data/`。
- 解压清单 `manifest.json` 保存原始路径、规范化路径、文件大小和 SHA-256；提取工具位于 `repos/happyro-gateway/tools/extract-grf.mjs`。
- 根仓库资源工具根据 `item-assets.json` 将官方 BMP 批量转换为透明 PNG，默认写入被 Git 忽略的 `work/game-data/items/kro-20211105/`；管理后台只读取这批生成结果，不在请求期间读取或转换 BMP。
- 地图名称和图片来自 HappyRO 客户端资源，NPC 位置来自 HappyRO Server 脚本，NPC 中文名称和形象来自客户端名称表及 SPR。
- 仓库保留 537 张有效地图图片和 1453 张 NPC 图片；官方纯色占位地图不作为真实图片发布。
- NPC 图片由客户端构建脚本合成为 WebP 图集，游戏内导航和 NPC 图鉴共享 `npc-assets.json` 与图集；后台继续使用独立 PNG。
- 地图图片按需加载，选中地图时读取对应 GAT；无真实地图图片时使用 GAT 预览，不在运行时生成地图位图文件。

## 资源加载

- 列表图标的源文件为 `texture/유저인터페이스/item/<资源名>.bmp`，详情插图的源文件为 `texture/유저인터페이스/collection/<资源名>.bmp`；离线生成器全图清除客户端约定的粉红透明键色，并只对其他颜色清除与边缘连通的背景，保留物品本体。
- Laravel 根据物品 ID 读取预生成的 `icons/<id>.png` 与 `illustrations/<id>.png`，并通过受权限保护的后台或游戏会话接口返回图片。
- `/api/game-data/items/{id}/icon` 返回 24×24 游戏图标；`/api/game-data/items/{id}/illustration` 返回通常为 75×100 的详情插图。
- 图片响应使用独立的资源请求限流额度和一天浏览器缓存；前端列表固定 48×64 容器，详情固定 75×100，缺失插图时回退到图标或本地占位图。
- 客户端 BMP 说明中的颜色控制码和空白占位行在 Laravel 服务层清理后再返回，避免把 `^777777` 等内部标记显示给管理员。

## 版本模型

- `客户端资源` 与 `服务端版本` 是两个独立的全局配置，不合并为一个版本号，也不在各图鉴的筛选区域重复选择。
- 客户端资源当前为 `kRO 2021-11-05`（内部版本 `kro-20211105`），决定 `itemInfo_true.json`、`data.grf`、图片和客户端说明的来源。
- 服务端版本来自 HappyRO Server 的 rAthena 数据快照，英文基线为 `2fe6ab3dc4d8`；完整 hash 保存在资料目录和全局设置中。
- 当前版本组合保存在 `happyro_admin.game_data_settings` 单例记录中，由“系统设置 / 游戏资料”统一修改，对全部管理员、图鉴 API 和运营功能生效。
- 可选客户端版本必须存在于客户端物品目录；可选服务端版本必须同时存在于物品与魔物 Renewal 目录，避免设置后部分图鉴变为空数据。

## 数据范围

图鉴提供三个互斥的数据范围选项，内部值固定为 `client`、`server`、`all`：

- `仅客户端`：只显示客户端 `itemInfo_true.json` 快照中的物品 ID；这不是扫描 GRF 中所有图片文件。
- `仅服务端`：只显示 rAthena `item_db_*.yml` 中的物品 ID。
- `全部`：按物品 ID 对客户端和服务端集合取并集。

默认范围为 `仅客户端`，发放物品使用服务端物品集合并单独校验服务端可发放属性。

数据范围只决定物品 ID 的列表集合，不限制列表记录的内容来源。确定候选 ID 后，系统必须按同一 ID 合并客户端、服务端和其他已登记的本地资源数据，再返回完整记录。也就是说，`仅客户端` 仍然只列出客户端集合中的 ID，但这些 ID 如果在服务端有对应定义，仍应使用服务端数据补充 AegisName、类型、重量、价格和脚本等字段。

## 数据合并规则

- `全部` 范围按物品 ID 去重，每个 ID 只返回一条记录，并保留 `source`：`client`、`server` 或 `both`。
- 客户端字段包括显示名称、说明、资源名、图标和详情插图；服务端字段包括 `AegisName`、服务端名称、类型、重量、价格、攻击、槽位、限制和脚本。其他本地索引可补充资源状态和检索字段。
- 同一 ID 的客户端名称与服务端名称都保留；有客户端名称时默认显示客户端名称，没有时回退到服务端名称。两边的说明不互相伪造。
- 物品名称统一使用 `names` 映射，例如 `{"zh-CN":"剑","en-US":"Sword"}`；不再使用 `Name` 或 `NameEn` 字段。
- 合并后的记录应保留字段来源或可诊断的来源元数据；字段没有任何来源时才为空，不能因为列表范围是客户端就清空可用的服务端补充字段。
- `source` 表示候选 ID 在客户端和服务端集合中的成员关系，不表示每个字段的来源。字段筛选和详情展示都针对合并后的有效记录执行。

## 查询实现方案

- 第一步按数据范围查询候选 ID：客户端集合、服务端集合或两者并集。
- 第二步按候选 ID 批量加载各版本资料，并由统一的合并组件生成有效记录；禁止在控制器中分别拼装字段。
- 第三步在有效记录的检索投影上执行名称、AegisName、类型和武器类型等筛选。`仅客户端` 中有服务端同 ID 定义的物品可以按服务端类型筛选；没有服务端定义的客户端专有物品无法凭空推断类型。
- 列表和详情必须使用同一合并组件，保证筛选结果与详情字段一致；CLI、HTTP API 和后续其他入口也复用该组件。
- 当前数据库按来源目录保存规范化原始记录；查询服务将所选客户端和服务端目录作为补充来源，在数据库中完成候选 ID、字段筛选和批量加载，不在请求期间解析 JSON 或猜测字段。后续数据量或版本组合显著增加时，再将同一合并规则物化为按版本组合的查询投影，不能改变接口语义。

## 数据库存储

- 游戏资料使用独立的 `happyro_admin` MariaDB，不写入玩家数据库，也不使用 SQLite 作为运行时存储。
- `game_data_catalogs` 保存资料类型、客户端或服务端来源、规则集、源版本、内容哈希和导入时间。
- `game_items` 保存稳定且全局唯一的物品 ID；`game_item_sources` 按资料目录保存客户端或服务端的规范化来源记录。
- `game_item_views` 保存客户端版本与 Renewal 服务端版本组合的合并查询投影，包括集合成员标记、常用筛选字段、完整属性和字段来源。
- `item_subtype` 来自 rAthena `item_db_equip.yml` 的 `SubType`；来源表保留规范化值，合并视图使用独立列支持武器类型筛选。普通类型查询和武器细分查询分别使用覆盖 `item_type` 与 `item_type + item_subtype` 的组合索引。
- 只有选择 `Type=Weapon` 时才显示武器类型筛选；切换到其他物品类型时隐藏并清空武器类型。稳定值与 rAthena 保持一致，显示名称由前端国际化资源提供。
- `php artisan game-data:import-items --all` 导入客户端与 Renewal 服务端快照，并在导入后重建合并投影；HTTP API 只查询 `game_item_views`，不在请求期间解析大体积 JSON。
- 物品、魔物仓储通过可注入的 `GameDataSettingRepository` 获取全局版本，HTTP 查询参数不再接受客户端或服务端版本，CLI 和运营用例使用同一配置来源。
- 导入以 `(resource_type, source, ruleset, source_version)` 定位资料目录，并按 `(game_data_catalog_id, game_item_id)` 执行来源 upsert；每轮同步会删除同一版本中已不存在的记录，重复执行结果一致。
- `php artisan game-data:rebuild-item-views --run` 可从来源记录独立重建全部版本组合；不带 `--run` 时只输出帮助，不执行写操作。

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

- 使用根仓库 `tools/resources/catalog/main.py items server` 生成后台本地服务端物品快照。
- 使用根仓库 `tools/resources/catalog/main.py items client` 一次生成仅客户端快照、资源路径映射和说明索引。
- 使用根仓库 `tools/resources/catalog/main.py items images` 根据资源映射一次生成全部透明 PNG；输入仍是只读的 GRF 解压目录，运行时不承担图片转换成本。
- 产物统一写入 `backend/resources/game-data/items/`，生成完成后再由 Laravel 导入命令同步至 MariaDB。
- 服务端工具负责读取 rAthena 数据并生成 Renewal 快照；默认使用服务端英文基线 `2fe6ab3dc4d8`，禁止从已翻译的服务端文件猜测英文名称。
- 仅客户端工具读取 kRO merged 的 `itemInfo_true.json`，用 Renewal 快照中的 `en-US` 名称补齐英文；生成前要求客户端 ID 全部命中服务端快照。
- 资源路径必须以 GRF 解压 `manifest.json` 为准：先精确匹配，再进行 Unicode NFC、不区分大小写和 CP949 乱码恢复匹配，将磁盘真实相对路径写入 `item-assets.json`。禁止在 Laravel 请求期间扫描目录或猜测文件名。
- 两条生成流水线共用通用游戏资料构建工程，但客户端和服务端的领域规则互相隔离；完整结构、参数和测试命令见根仓库 `tools/resources/README.md`。

## 魔物资料

- `python3 tools/resources/catalog/main.py monsters` 从当前 Renewal `mob_db.yml` 读取中文服务端属性，并从 Git 基线 `2fe6ab3dc4d8` 读取英文名称。
- 客户端名称和精灵名读取 HappyRO Client 的 `MonsterNameTable.js` 与 `MonsterTable.js`，精灵文件来自只读的 kRO 2021-11-05 GRF 解压目录。
- 工具生成 `backend/resources/game-data/monsters/renewal.json` 和 `monster-assets.json`；PNG 写入被 Git 忽略的 `work/game-data/monsters/kro-20211105/`。
- `php artisan game-data:import-monsters --renewal` 按服务端版本幂等写入 MariaDB，HTTP 请求不解析 YAML、JavaScript 或 SPR。
- SPR 版本或客户端映射缺失时保留服务端魔物记录并显示空形象，不以其他魔物图片代替。

## 验收记录

- GRF 全量提取 `148,805/148,805` 成功，解压后约 `11.4 GiB`。
- 物品 ID `1101` 的说明可正常返回并清理客户端格式码。
- 物品 ID `1107` 的详情插图可离线生成并解析为带透明背景的 `75×100` PNG。
- 物品 ID `1100` 等没有客户端说明的物品明确显示缺省文案，不使用服务器脚本伪造说明。
- 地图和 NPC 查询已通过登录状态的真实 Chromium 验收；无地图资源时显示居中的“暂无图片”。

## 安全边界

- 发放权限使用 `operations.item-grant`，资料查询使用 `game-data.view`。
- 全局资料版本设置使用 `settings.manage`，读取和修改接口均不对普通资料查看者开放；修改会记录操作人、修改前后版本和客户端上下文。
- 物品图鉴操作列为具备发放权限的管理员提供发放入口，预填当前物品并复用运营管理的邮件发放表单和后端用例。
- 收件目标可按角色 ID、角色名或账号用户名实时检索；检索接口使用 `target`，选定后发放接口仍提交唯一的 `char_id`。
- 运营管理的发放页面可按物品 ID 或双语名称实时检索服务端可发放物品；选定后发放接口提交唯一的 `item_id`。
- 第一版只支持邮件发放，不直接写入 `inventory`，避免在线角色内存状态和物品唯一 ID 冲突。
- 每次发放写入审计事件，后续增加发放记录页面。

## 待办

- 补充其他语言的官方物品说明快照，并按后台当前语言选择说明语言。
- 增加按账号选择角色和物品选择器。
- 增加发放记录查询和重复提交保护。
- 评估通过游戏服务接口支持直接发放背包，而不是直接操作背包表。
