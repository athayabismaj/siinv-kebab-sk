<?php

namespace App\Http\Middleware;

use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApiTokenMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $bearerToken = $request->bearerToken();
        if (! $bearerToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token autentikasi tidak ditemukan. Silakan login kembali.',
            ], 401);
        }

        $tokenHash = hash('sha256', $bearerToken);

        $user = User::query()
            ->join('api_tokens', 'api_tokens.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('api_tokens.token_hash', $tokenHash)
            ->select([
                'users.*',
                'api_tokens.id as auth_token_id',
                'api_tokens.last_used_at as auth_token_last_used_at',
                'api_tokens.expires_at as auth_token_expires_at',
                'roles.id as auth_role_id',
                'roles.name as auth_role_name',
            ])
            ->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak ditemukan atau telah dinonaktifkan. Silakan login kembali.',
            ], 401);
        }

        $expiresAt = $user->auth_token_expires_at
            ? Carbon::parse($user->auth_token_expires_at)
            : null;
        if ($expiresAt && now()->greaterThan($expiresAt)) {
            DB::table('api_tokens')->where('id', $user->auth_token_id)->delete();

            return response()->json([
                'success' => false,
                'message' => 'Sesi Anda telah berakhir. Silakan login kembali.',
            ], 401);
        }

        $lastUsedAt = $user->auth_token_last_used_at
            ? Carbon::parse($user->auth_token_last_used_at)
            : null;
        if (! $lastUsedAt || $lastUsedAt->lt(now()->subMinutes(5))) {
            DB::table('api_tokens')
                ->where('id', $user->auth_token_id)
                ->update(['last_used_at' => now()]);
        }

        $role = new Role(['name' => (string) $user->auth_role_name]);
        $role->id = (int) $user->auth_role_id;
        $role->exists = true;
        $user->setRelation('role', $role);

        Auth::setUser($user);
        $request->setUserResolver(static fn () => $user);

        return $next($request);
    }
}
