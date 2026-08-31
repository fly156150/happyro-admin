<?php

namespace App\Providers;

use App\Contracts\Audit\AuditWriter;
use App\Contracts\Auth\AuthenticationService;
use App\Contracts\Auth\LoginThrottle;
use App\Contracts\Auth\PermissionChecker;
use App\Contracts\Auth\UserProvisioner;
use App\Contracts\Auth\UserRepository;
use App\Services\Audit\EloquentAuditWriter;
use App\Services\Auth\EloquentPermissionChecker;
use App\Services\Auth\EloquentUserProvisioner;
use App\Services\Auth\EloquentUserRepository;
use App\Services\Auth\RateLimiterLoginThrottle;
use App\Services\Auth\SessionAuthenticationService;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
