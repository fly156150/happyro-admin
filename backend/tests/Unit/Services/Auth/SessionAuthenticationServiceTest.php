<?php

namespace Tests\Unit\Services\Auth;

use App\Contracts\Audit\AuditWriter;
use App\Contracts\Auth\LoginThrottle;
use App\Contracts\Auth\UserRepository;
use App\Data\Auth\ClientContext;
use App\Data\Auth\LoginData;
use App\Data\Auth\LoginStatus;
use App\Models\User;
use App\Services\Auth\SessionAuthenticationService;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\StatefulGuard;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class SessionAuthenticationServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private AuthFactory $auth;

    private StatefulGuard $guard;

    private UserRepository $users;

    private LoginThrottle $throttle;

    private AuditWriter $audit;

    private SessionAuthenticationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth = Mockery::mock(AuthFactory::class);
        $this->guard = Mockery::mock(StatefulGuard::class);
        $this->users = Mockery::mock(UserRepository::class);
        $this->throttle = Mockery::mock(LoginThrottle::class);
        $this->audit = Mockery::mock(AuditWriter::class);
        $this->auth->allows('guard')->with('web')->andReturn($this->guard);
        $this->service = new SessionAuthenticationService(
            $this->auth,
            $this->users,
            $this->throttle,
            $this->audit,
        );
    }

    public function test_it_authenticates_an_active_user_and_records_the_login(): void
    {
        [$data, $context] = $this->loginInput();
        $user = new User(['username' => 'admin', 'name' => 'Admin', 'is_active' => true]);
        $this->throttle->expects('isLocked')->with($data, $context)->andReturnFalse();
        $this->users->expects('findByUsername')->with('admin')->andReturn($user);
        $this->guard->expects('attempt')->with([
            'username' => 'admin',
            'password' => 'correct-password',
            'is_active' => true,
        ], true)->andReturnTrue();
        $this->throttle->expects('clear')->with($data, $context);
        $this->users->expects('recordLogin')->with($user, $context);
        $this->audit->expects('write')->with(
            'auth.login_succeeded',
            $context,
            $user,
            'admin',
        );

        $result = $this->service->login($data, $context);

        $this->assertSame(LoginStatus::Success, $result->status);
        $this->assertSame($user, $result->user);
    }

    public function test_it_rejects_a_disabled_user_without_checking_the_password(): void
    {
        [$data, $context] = $this->loginInput();
        $user = new User(['username' => 'admin', 'name' => 'Admin', 'is_active' => false]);
        $this->throttle->expects('isLocked')->andReturnFalse();
        $this->users->expects('findByUsername')->with('admin')->andReturn($user);
        $this->guard->shouldNotReceive('attempt');
        $this->throttle->expects('hit')->with($data, $context);
        $this->audit->expects('write')->with(
            'auth.login_failed',
            $context,
            $user,
            'admin',
            ['reason' => 'disabled'],
        );

        $result = $this->service->login($data, $context);

        $this->assertSame(LoginStatus::InvalidCredentials, $result->status);
    }

    public function test_it_stops_before_loading_a_user_when_rate_limited(): void
    {
        [$data, $context] = $this->loginInput();
        $this->throttle->expects('isLocked')->with($data, $context)->andReturnTrue();
        $this->throttle->expects('availableIn')->with($data, $context)->andReturn(42);
        $this->users->shouldNotReceive('findByUsername');
        $this->audit->expects('write')->with('auth.login_blocked', $context, null, 'admin');

        $result = $this->service->login($data, $context);

        $this->assertSame(LoginStatus::RateLimited, $result->status);
        $this->assertSame(42, $result->retryAfter);
    }

    public function test_it_audits_before_logging_out(): void
    {
        $context = new ClientContext('127.0.0.1', 'PHPUnit');
        $user = new User(['username' => 'admin', 'name' => 'Admin']);
        $this->audit->expects('write')->ordered()->with('auth.logout', $context, $user, 'admin');
        $this->guard->expects('logout')->ordered();

        $this->service->logout($user, $context);
    }

    /** @return array{LoginData, ClientContext} */
    private function loginInput(): array
    {
        return [
            new LoginData('admin', 'correct-password', true),
            new ClientContext('127.0.0.1', 'PHPUnit'),
        ];
    }
}
