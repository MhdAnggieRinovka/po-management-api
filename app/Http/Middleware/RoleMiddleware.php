<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            abort(403, 'Akun tidak aktif atau tidak memiliki akses.');
        }

        if ($user->role !== 'admin' && ! in_array($user->role, $roles, true)) {
            abort(403, 'Role pengguna tidak memiliki izin untuk tindakan ini.');
        }

        return $next($request);
    }
}
