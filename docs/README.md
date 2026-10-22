# Admin 文档

- [架构](architecture.md)：数据库、资料和游戏服务边界。
- [前端](frontend.md)：开发入口与检查。后端开发与部署说明由独立的 happyro-api 仓库维护。
- [部署](deployment.md)：Docker／原生环境、认证、配置与升级。
- [角色命令契约](product/game-control-commands.md)：成长、属性、职业外观、骑乘及服务端权威校验。
- [产品与待办](product/README.md)：用户、资料、运营、设置及 Game Control。

根 README 是入口，子应用 README 只说明本目录职责；规则与命令维护在本目录。完成的阶段计划、重复框架教程和测试数量不继续维护，历史从 changelog／Git 查询。

## 发布与变更记录

本仓库随 HappyRO 统一版本交付，目标版本读取根仓库 `deploy/docker/VERSION`。准备中的功能和升级要求见[版本摘要](../../../changelog/releases/v0.5.0.md)，本仓库原始变化见 [changelog](../changelog/)。镜像构建与最终包验收按根仓库[发布流程](../../../docs/operations/docker-release.md)执行，文档整理不表示已经发布。
