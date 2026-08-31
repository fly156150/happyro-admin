<?php

namespace App\Services\Auth;

use App\Contracts\Audit\AuditWriter;
use App\Contracts\Auth\AuthenticationService;
use App\Contracts\Auth\LoginThrottle;
use App\Contracts\Auth\UserRepository;
use App\Data\Auth\ClientContext;
use App\Data\Auth\LoginData;
use App\Data\Auth\LoginResult;
use App\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\StatefulGuard;
use LogicException;

final class SessionAuthenticationService implements AuthenticationService
{
    public function __construct(
        private readonly AuthFactory $auth,
        private readonly UserRepository $users,
        private readonly LoginThrottle $throttle,
        private readonly AuditWriter $audit,
    ) {}

    public function login(LoginData $data, ClientContext $context): LoginResult
    {
        if ($this->throttle->isLocked($data, $context)) {
            $this->audit->write('auth.login_blocked', $context, username: $data->username);

            return LoginResult::rateLimited($this->throttle->availableIn($data, $context));
        }

        $user = $this->users->findByUsername($data->username);
        $guard = $this->guard();
        $authenticated = $user?->is_active === true && $guard->attempt([
            'username' => $data->username,
            'password' => $data->password,
            'is_active' => true,
        ], $data->remember);

        if (! $authenticated) {
            $this->throttle->hit($data, $context);
            $this->audit->write(
                'auth.login_failed',
                $context,
                $user,
                $data->username,
                ['reason' => $user?->is_active === false ? 'disabled' : 'invalid_credentials'],
            );

            return LoginResult::invalidCredentials();
        }

        $this->throttle->clear($data, $context);
        $this->users->recordLogin($user, $context);
        $this->audit->write('auth.login_succeeded', $context, $user, $user->username);

        return LoginResult::success($user);
    }

    public function logout(User $user, ClientContext $context): void
    {
        $this->audit->write('auth.logout', $context, $user, $user->username);
        $this->guard()->logout();
    }

    private function guard(): StatefulGuard
    {
        $guard = $this->auth->guard('web');

        if (! $guard instanceof StatefulGuard) {
            throw new LogicException('The web authentication guard must be stateful.');
        }

        return $guard;
    }
}
