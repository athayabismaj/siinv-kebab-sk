<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        if ($user->trashed()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        $userRole = strtolower($user->role->name);

        // Developer (Super Admin) memiliki akses bypass ke semua halaman
        if ($userRole === 'developer') {
            return $next($request);
        }

        // Pengecekan multi-role jika middleware memisahkan dengan pipe, misal: 'role:admin|owner'
        $allowedRoles = explode('|', strtolower($role));

        if (! in_array($userRole, $allowedRoles)) {
            abort(403);
        }

        return $next($request);
    }
}
