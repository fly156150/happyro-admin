<?php

namespace App\Providers;

use App\Contracts\Audit\AuditLogRepository;
use App\Contracts\Audit\AuditWriter;
use App\Contracts\Auth\AuthenticationService;
use App\Contracts\Auth\LoginThrottle;
use App\Contracts\Auth\PermissionChecker;
use App\Contracts\Auth\UserProvisioner;
use App\Contracts\Auth\UserRepository;
use App\Contracts\GameData\GameDataSettingRepository;
use App\Contracts\GameData\ItemAssetRepository;
use App\Contracts\GameData\ItemCatalogRepository;
use App\Contracts\GameData\ItemRepository;
use App\Contracts\GameData\ItemSnapshotReader;
use App\Contracts\GameData\ItemViewBuilder;
use App\Contracts\GameData\MonsterAssetRepository;
use App\Contracts\GameData\MonsterCatalogRepository;
use App\Contracts\GameData\MonsterRepository;
use App\Contracts\GameData\MonsterSnapshotReader;
use App\Contracts\GameServer\GameServerCommandRepository;
use App\Contracts\GameServer\GameServerConfigWriter;
use App\Contracts\GameServer\GameServerGateway;
use App\Contracts\GameServer\GameServerSettingRepository;
use App\Contracts\GameServer\GameServerSettingRevisionRepository;
use App\Contracts\Operations\ItemGrantRecordRepository;
use App\Contracts\Operations\ItemGrantRepository;
use App\Contracts\Operations\ItemGrantTargetRepository;
use App\Contracts\Players\LoginLogRepository;
use App\Contracts\Players\PlayerAccountRepository;
use App\Contracts\Players\PlayerCharacterRepository;
use App\Infrastructure\GameServer\FileGameServerConfigWriter;
use App\Infrastructure\GameServer\HttpGameServerGateway;
use App\Infrastructure\Persistence\Audit\EloquentAuditLogRepository;
use App\Infrastructure\Persistence\GameData\DatabaseItemCatalogRepository;
use App\Infrastructure\Persistence\GameData\DatabaseMonsterCatalogRepository;
use App\Infrastructure\Persistence\GameServer\DatabaseGameServerCommandRepository;
use App\Infrastructure\Persistence\GameServer\DatabaseGameServerSettingRepository;
use App\Infrastructure\Persistence\GameServer\DatabaseGameServerSettingRevisionRepository;
use App\Infrastructure\Persistence\Operations\EloquentItemGrantRecordRepository;
use App\Services\Audit\EloquentAuditWriter;
use App\Services\Auth\EloquentPermissionChecker;
use App\Services\Auth\EloquentUserProvisioner;
use App\Services\Auth\EloquentUserRepository;
use App\Services\Auth\RateLimiterLoginThrottle;
use App\Services\Auth\SessionAuthenticationService;
use App\Services\GameData\DatabaseGameDataSettingRepository;
use App\Services\GameData\DatabaseItemRepository;
use App\Services\GameData\DatabaseItemViewBuilder;
use App\Services\GameData\DatabaseMonsterRepository;
use App\Services\GameData\JsonItemSnapshotReader;
use App\Services\GameData\JsonMonsterSnapshotReader;
use App\Services\GameData\LocalItemAssetRepository;
use App\Services\GameData\LocalMonsterAssetRepository;
use App\Services\Operations\DatabaseItemGrantRepository;
use App\Services\Operations\DatabaseItemGrantTargetRepository;
use App\Services\Operations\ItemGrantService;
use App\Services\Players\DatabaseLoginLogRepository;
use App\Services\Players\DatabasePlayerAccountRepository;
use App\Services\Players\DatabasePlayerCharacterRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthenticationService::class, SessionAuthenticationService::class);
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(UserProvisioner::class, EloquentUserProvisioner::class);
        $this->app->bind(LoginThrottle::class, RateLimiterLoginThrottle::class);
        $this->app->bind(AuditWriter::class, EloquentAuditWriter::class);
        $this->app->bind(AuditLogRepository::class, EloquentAuditLogRepository::class);
        $this->app->bind(PermissionChecker::class, EloquentPermissionChecker::class);
        $this->app->bind(PlayerAccountRepository::class, DatabasePlayerAccountRepository::class);
        $this->app->bind(PlayerCharacterRepository::class, DatabasePlayerCharacterRepository::class);
        $this->app->bind(ItemRepository::class, DatabaseItemRepository::class);
        $this->app->bind(ItemCatalogRepository::class, DatabaseItemCatalogRepository::class);
        $this->app->bind(GameDataSettingRepository::class, DatabaseGameDataSettingRepository::class);
        $this->app->bind(ItemSnapshotReader::class, JsonItemSnapshotReader::class);
        $this->app->bind(ItemViewBuilder::class, DatabaseItemViewBuilder::class);
        $this->app->bind(ItemAssetRepository::class, function () {
            return new LocalItemAssetRepository(
                config('happyro.game_data.item_image_root'),
            );
        });
        $this->app->bind(ItemGrantRepository::class, DatabaseItemGrantRepository::class);
        $this->app->bind(ItemGrantRecordRepository::class, EloquentItemGrantRecordRepository::class);
        $this->app->bind(MonsterRepository::class, DatabaseMonsterRepository::class);
        $this->app->bind(MonsterCatalogRepository::class, DatabaseMonsterCatalogRepository::class);
        $this->app->bind(MonsterSnapshotReader::class, JsonMonsterSnapshotReader::class);
        $this->app->bind(MonsterAssetRepository::class, fn () => new LocalMonsterAssetRepository(
            config('happyro.game_data.monster_image_root'),
        ));
        $this->app->bind(GameServerCommandRepository::class, DatabaseGameServerCommandRepository::class);
        $this->app->bind(GameServerSettingRevisionRepository::class, DatabaseGameServerSettingRevisionRepository::class);
        $this->app->bind(GameServerSettingRepository::class, DatabaseGameServerSettingRepository::class);
        $this->app->bind(GameServerConfigWriter::class, fn () => new FileGameServerConfigWriter(config('happyro.game_control.battle_config_path')));
        $this->app->bind(GameServerGateway::class, fn ($app) => new HttpGameServerGateway(
            $app->make(Factory::class),
            config('happyro.game_control.base_url'),
            config('happyro.game_control.token'),
            config('happyro.game_control.connect_timeout'),
            config('happyro.game_control.timeout'),
        ));
        $this->app->bind(ItemGrantTargetRepository::class, DatabaseItemGrantTargetRepository::class);
        $this->app->bind(ItemGrantService::class);
        $this->app->bind(LoginLogRepository::class, DatabaseLoginLogRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('adventure-tools-read', function (Request $request): Limit {
            $principal = $request->attributes->get('game_session');
            $key = $principal === null
                ? 'anonymous:'.$request->ip()
                : 'game-account:'.$principal->accountId;

            return Limit::perMinute(120)->by($key);
        });
        RateLimiter::for('adventure-tools-action', function (Request $request): Limit {
            $principal = $request->attributes->get('game_session');
            $key = $principal === null
                ? 'anonymous:'.$request->ip()
                : 'game-account:'.$principal->accountId;

            return Limit::perMinute(30)->by($key);
        });
        RateLimiter::for('adventure-tools-assets', function (Request $request): Limit {
            $principal = $request->attributes->get('game_session');
            $key = $principal === null
                ? 'anonymous:'.$request->ip()
                : 'game-account:'.$principal->accountId;

            return Limit::perMinute(600)->by($key);
        });
    }
}
