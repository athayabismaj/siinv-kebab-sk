<?php

use Illuminate\Foundation\Application;
use App\Http\Middleware\ApiTokenMiddleware;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function ($middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'api.token' => ApiTokenMiddleware::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, \Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $e instanceof QueryException) {
                return null;
            }

            $message = strtolower($e->getMessage());
            $isConnectionIssue =
                str_contains($message, 'sqlstate[08006]')
                || str_contains($message, 'connection to server')
                || str_contains($message, 'connection failure during authentication')
                || str_contains($message, 'could not connect to server')
                || str_contains($message, 'permission denied');

            if (! $isConnectionIssue) {
                return null;
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Koneksi database sedang bermasalah. Silakan coba lagi beberapa saat.',
                ], 503);
            }

            if ($request->isMethod('get')) {
                return response()->view('errors.database-unavailable', [], 503);
            }

            return back()->withInput()->withErrors([
                'system' => 'Koneksi database sedang bermasalah. Silakan coba lagi beberapa saat.',
            ]);
        });
    })->create();
