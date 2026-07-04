<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiRoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $userRole = strtolower(trim((string) optional($request->user()?->role)->name));
        $allowedRoles = collect($roles)
            ->flatMap(fn (string $role) => explode('|', $role))
            ->map(fn (string $role) => strtolower(trim($role)))
            ->filter()
            ->values()
            ->all();

        if ($userRole === '' || ! in_array($userRole, $allowedRoles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses tidak diizinkan.',
            ], 403);
        }

        return $next($request);
    }
}
