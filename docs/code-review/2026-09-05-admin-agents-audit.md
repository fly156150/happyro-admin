# Admin AGENTS 合规审计

## 已整改

- 控制器不再直接查询发放记录、审计记录或游戏规则历史；这些访问通过仓储契约完成。
- 发放幂等记录和游戏资料导入的持久化边界已移入 Infrastructure/Persistence。
- `ApplyGameServerSettingsRequest` 使用显式容器方法注入，不再调用 `app()`。
- 全局请求反馈使用 Ant Design `App.useApp()` 上下文；远程搜索选择器使用 Ant Design v6 `showSearch` 对象 API。
- 生产路由中的物品图鉴和账号管理页面已拆分超长组件函数。
- 所有四个资料相关 CLI 命令均验证无参数只输出帮助；帮助输出支持 ANSI 和 `--no-color`。
- 魔物种族、属性和体型映射已记录 HappyRO Server 源文件。

## 明确例外

函数长度扫描仍会发现若干超过 150 行的 Ant Design Pro 上游示例页，以及 `dashboard/monitor` 的静态地图演示组件。这些页面未被 `config/routes.ts` 注册，也不属于 HappyRO 产品路径；本次不删除以保留模板升级参考。若后续启用任一示例页，应在启用前拆分函数并补充对应测试。

`frontend/src/pages/dashboard/monitor/mock/map-grid.ts` 等静态地图数据文件是演示 fixture，不包含业务逻辑；其体积超过 800 行属于数据规模例外。

## 验证记录

- Backend PHPUnit：90 tests / 345 assertions，随机顺序通过。
- Backend Pint：通过；Composer audit：无安全公告。
- Frontend Vitest：66 tests 通过。
- Frontend Biome、TypeScript、Ant Design lint：通过。
- Frontend production build：通过。
