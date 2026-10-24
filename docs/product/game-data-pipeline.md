# 游戏资料导入与资源流水线

[产品行为与查询语义](game-data.md) · [部署](../deployment.md)

## 数据来源

- 服务器属性来自 HappyRO Server `db/re/item_db_*.yml`。
- 客户端 `itemInfo_true.json` 是从 Lua 5.1 字节码离线提取的规范化快照；说明和资源路径通过离线索引写入 `descriptions.json`、`item-assets.json`。
- 原始 kRO 资源来自 `inputs/runtime/kro-20211105/client/data.grf`，全量解压结果位于被 Git 忽略的 `work/grf-extract/kro-20211105/data/`。
- 解压清单 `manifest.json` 保存原始路径、规范化路径、文件大小和 SHA-256；提取工具位于 `repos/happyro-gateway/tools/extract-grf.mjs`。
- 根仓库资源工具根据 `item-assets.json` 将官方 BMP 批量转换为透明 PNG，默认写入被 Git 忽略的 `work/game-data/items/kro-20211105/`；管理后台只读取这批生成结果，不在请求期间读取或转换 BMP。
- 地图名称和图片来自 HappyRO 客户端资源，NPC 位置来自 HappyRO Server 脚本，NPC 中文名称和形象来自客户端名称表及 SPR。
- 图片覆盖以当前资源清单为准；官方纯色占位地图不作为真实图片发布。
- NPC 图片由客户端构建脚本合成为 WebP 图集，游戏内导航和 NPC 图鉴共享 `npc-assets.json` 与图集；后台继续使用独立 PNG。
- 地图图片按需加载，选中地图时读取对应 GAT；无真实地图图片时使用 GAT 预览，不在运行时生成地图位图文件。

## 资源加载

- 列表图标的源文件为 `texture/유저인터페이스/item/<资源名>.bmp`，详情插图的源文件为 `texture/유저인터페이스/collection/<资源名>.bmp`；离线生成器全图清除客户端约定的粉红透明键色，并只对其他颜色清除与边缘连通的背景，保留物品本体。
- Go 根据物品 ID 读取预生成的 `icons/<id>.png` 与 `illustrations/<id>.png`，并通过受权限保护的后台或游戏会话接口返回图片。
- `/api/game-data/items/{id}/icon` 返回 24×24 游戏图标；`/api/game-data/items/{id}/illustration` 返回通常为 75×100 的详情插图。
- 图片响应使用独立的资源请求限流额度和一天浏览器缓存；前端列表固定 48×64 容器，详情固定 75×100，缺失插图时回退到图标或本地占位图。
- 客户端 BMP 说明中的颜色控制码和空白占位行在 Go 服务层清理后再返回，避免把 `^777777` 等内部标记显示给管理员。

## 数据库存储

- 游戏资料使用独立的 `happyro_admin` MariaDB，不写入玩家数据库，也不使用 SQLite 作为运行时存储。
- `catalog_generations` 保存四类快照的版本、内容哈希、元数据和条目数量；`catalog_state` 指向当前发布代次。
- `catalog_items` 保存客户端与服务端合并投影、集合成员标记和字段来源；`catalog_monsters`、`catalog_drops` 与 `catalog_npcs` 保存同一代次的其他资料。
- 物品子类由 `internal/catalog/subtype.go` 从声明类型和装备位置计算；客户端离线映射由 `catalog export-subtypes` 复用同一规则。
- `catalog import` 必须同时指定客户端物品、Renewal 物品、魔物和 NPC 四个快照。先校验完整快照，再事务写入新代次并切换当前指针；失败不会发布半套资料，内容未变化时不创建新代次。
- HTTP 查询读取已发布代次，不在请求期间解析大体积 JSON。完整命令见[部署文档](../deployment.md)。

## 部署资源

当前 Docker Go 进程同时提供前端静态文件和 API；图片通过现有受权限／游戏会话保护的接口读取挂载的生成目录。不能将尚未配置的 `/game-assets/` 静态 URL 或一年 immutable 缓存视为现有部署事实。

原始 GRF 只读，HTTP 请求不解压或转换 BMP／SPR。部署时核对图片根目录、挂载与缓存策略；若以后改为 Nginx 直接提供图片，需要同时设计访问控制、URL 和失效策略，并验证后台与冒险工具，不能只加一条 alias。

## 快照生成

- 使用根仓库 `tools/resources/catalog/main.py items server` 生成后台本地服务端物品快照。
- 使用根仓库 `tools/resources/catalog/main.py items client` 一次生成仅客户端快照、资源路径映射和说明索引。
- 使用根仓库 `tools/resources/catalog/main.py items images` 根据资源映射一次生成全部透明 PNG；输入仍是只读的 GRF 解压目录，运行时不承担图片转换成本。
- 产物统一写入 `happyro-api/resources/game-data/items/`，生成完成后再由 Go 导入命令同步至 MariaDB。
- 服务端工具负责读取 rAthena 数据并生成 Renewal 快照；默认使用服务端英文基线 `2fe6ab3dc4d8`，禁止从已翻译的服务端文件猜测英文名称。
- 仅客户端工具读取 kRO merged 的 `itemInfo_true.json`，用 Renewal 快照中的 `en-US` 名称补齐英文；生成前要求客户端 ID 全部命中服务端快照。
- 资源路径必须以 GRF 解压 `manifest.json` 为准：先精确匹配，再进行 Unicode NFC、不区分大小写和 CP949 乱码恢复匹配，将磁盘真实相对路径写入 `item-assets.json`。禁止在 Go 请求期间扫描目录或猜测文件名。
- 两条生成流水线共用通用游戏资料构建工程，但客户端和服务端的领域规则互相隔离；完整结构、参数和测试命令见根仓库 `tools/resources/README.md`。

## 魔物资料

- `python3 tools/resources/catalog/main.py monsters` 从当前 Renewal `mob_db.yml` 读取中文服务端属性，并从 Git 基线 `2fe6ab3dc4d8` 读取英文名称。
- 客户端名称和精灵名读取 HappyRO Client 的 `MonsterNameTable.js` 与 `MonsterTable.js`，精灵文件来自只读的 kRO 2021-11-05 GRF 解压目录。
- 工具生成 `happyro-api/resources/game-data/monsters/renewal.json` 和 `monster-assets.json`；PNG 写入被 Git 忽略的 `work/game-data/monsters/kro-20211105/`。
- 完整 `catalog import` 将魔物与其余快照一起原子发布到 MariaDB，HTTP 请求不解析 YAML、JavaScript 或 SPR。
- SPR 版本或客户端映射缺失时保留服务端魔物记录并显示空形象，不以其他魔物图片代替。


## NPC 资料

NPC 目录由根仓库 `tools/resources/world/generate-npc-catalog.mjs` 生成，随后运行 完整 `catalog import` 导入管理库。冒险工具 NPC 列表使用数据库分页，地图标记按单张地图查询。

## 名称更新后的交叉检查

魔物改名按 ID／AegisName 核对客户端、服务端、任务及图鉴来源；来源不明时保留现值，不能从网站名称推导服务器数值。根仓库 `python3 -m tools.resources.monsters.verify_application verify --no-color` 检查当前名称、默认封包短名、魔物型佣兵、普通刷怪及已审任务字段。

更新 JSON 后仍需完整 `catalog import`，后台 HTTP 查询读取已发布代次，不会因磁盘文件变化自动刷新。NPC 脚本即使只改对白，也需重新生成 NPC 目录以同步来源哈希，再同步客户端图集清单及数据库。
