<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Bloquea el acceso si el usuario autenticado no tiene el/los roles requeridos.
     *
     * Uso en rutas:
     *   ->middleware('role:admin')
     *   ->middleware('role:admin,supervisor')
     *
     * @param string $roles Roles permitidos separados por coma
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole($roles)) {
            // API request → JSON 403
            if ($request->expectsJson()) {
                return response()->json([
                    'data'   => null,
                    'meta'   => [],
                    'errors' => ['Este recurso está restringido a los roles: ' . implode(', ', $roles)],
                ], 403);
            }

            // Web request → Abort 403
            abort(403, 'No tienes permiso para acceder a este recurso.');
        }

        return $next($request);
    }
}
