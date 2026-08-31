<?php

namespace App\Http\Middleware;

use App\Contracts\Auth\PermissionChecker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePermission
{
    public function __construct(private readonly PermissionChecker $permissions) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        abort_unless($user?->is_active && $this->permissions->allows($user, $permission), 403);

        return $next($request);
    }
}
