# 架构

Admin 读取静态游戏资料和 MariaDB 中的玩家数据，并把改变世界状态的命令交给 Server 的 Game Control。

```text
frontend :8000
  → backend :18081
       ├── happyro_admin     管理员、角色、权限、审计
       ├── happyro           玩家查询
       ├── resources/game-data   物品 / 魔物 / 地图 / NPC JSON
       └── Game Control      map-server
```

后端按 Laravel 分层：控制器只做 HTTP，用例在 Service，数据访问在 Repository，外部依赖可 Mock。前端目录与一级菜单对齐。产品结构见 [product/README.md](product/README.md)。
