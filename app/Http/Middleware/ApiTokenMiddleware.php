<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiTokenMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $bearerToken = $request->bearerToken();
        if (! $bearerToken) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $tokenHash = hash('sha256', $bearerToken);

        $apiToken = ApiToken::query()
            ->with('user.role')
            ->where('token_hash', $tokenHash)
            ->first();

        if (! $apiToken || ! $apiToken->user || $apiToken->user->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        if ($apiToken->expires_at && now()->greaterThan($apiToken->expires_at)) {
            $apiToken->delete();

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $apiToken->update(['last_used_at' => now()]);

        $user = $apiToken->user;
        Auth::setUser($user);
        $request->setUserResolver(static fn () => $user);

        return $next($request);
    }
}
