<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PerformanceLogMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $enabled = filter_var(env('PERF_LOG_ENABLED', true), FILTER_VALIDATE_BOOL);
        if (! $enabled) {
            return $next($request);
        }

        $queryCount = 0;
        DB::listen(static function () use (&$queryCount) {
            $queryCount++;
        });

        $start = microtime(true);
        $response = $next($request);
        $durationMs = (microtime(true) - $start) * 1000;

        $slowMs = (int) env('PERF_LOG_SLOW_MS', 350);
        $isSlow = $durationMs >= $slowMs;

        if ($isSlow) {
            Log::warning('slow-request', [
                'method' => $request->method(),
                'path' => $request->path(),
                'route' => optional($request->route())->getName(),
                'status' => $response->getStatusCode(),
                'duration_ms' => round($durationMs, 2),
                'query_count' => $queryCount,
                'user_id' => optional($request->user())->id,
                'ip' => $request->ip(),
            ]);
        }

        return $response;
    }
}