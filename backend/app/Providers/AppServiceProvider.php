<?php

namespace App\Providers;

use App\Contracts\Audit\AuditWriter;
use App\Contracts\Auth\AuthenticationService;
use App\Contracts\Auth\LoginThrottle;
use App\Contracts\Auth\PermissionChecker;
use App\Contracts\Auth\UserProvisioner;
use App\Contracts\Auth\UserRepository;
use App\Contracts\Players\LoginLogRepository;
use App\Contracts\Players\PlayerAccountRepository;
use App\Contracts\Players\PlayerCharacterRepository;
use App\Services\Audit\EloquentAuditWriter;
use App\Services\Auth\EloquentPermissionChecker;
use App\Services\Auth\EloquentUserProvisioner;
use App\Services\Auth\EloquentUserRepository;
use App\Services\Auth\RateLimiterLoginThrottle;
use App\Services\Auth\SessionAuthenticationService;
use App\Services\Players\DatabaseLoginLogRepository;
use App\Services\Players\DatabasePlayerAccountRepository;
use App\Services\Players\DatabasePlayerCharacterRepository;
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
        $this->app->bind(PermissionChecker::class, EloquentPermissionChecker::class);
        $this->app->bind(PlayerAccountRepository::class, DatabasePlayerAccountRepository::class);
        $this->app->bind(PlayerCharacterRepository::class, DatabasePlayerCharacterRepository::class);
        $this->app->bind(LoginLogRepository::class, DatabaseLoginLogRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
