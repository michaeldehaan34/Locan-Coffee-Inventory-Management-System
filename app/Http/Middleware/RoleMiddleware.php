<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts access to routes based on the role stored in the session
 * during the role-based login (Manager / Barista).
 *
 * Usage: ->middleware('role:manager') or ->middleware('role:barista,manager')
 */
class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  string  ...$roles  Allowed roles.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->session()->get('role');

        // Auto-heal old session roles
        if ($role === 'manager') {
            $role = 'manajemen';
            $request->session()->put('role', 'manajemen');
        }

        if (! in_array($role, $roles, true)) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk halaman ini.');
        }

        return $next($request);
    }
}