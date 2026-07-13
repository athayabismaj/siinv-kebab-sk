<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestCorrelationMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $this->resolveRequestId($request->header('X-Request-ID'));

        $request->attributes->set('request_id', $requestId);
        Log::withContext(['request_id' => $requestId]);

        $response = $next($request);
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }

    private function resolveRequestId(mixed $providedId): string
    {
        $providedId = is_string($providedId) ? trim($providedId) : '';

        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,63}$/', $providedId) === 1) {
            return $providedId;
        }

        return (string) Str::uuid();
    }
}
