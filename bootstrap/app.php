<?php

use Illuminate\Foundation\Application;
use App\Http\Middleware\ApiTokenMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\PerformanceLogMiddleware;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

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
            'perf.log' => PerformanceLogMiddleware::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        // Render semua error sebagai JSON untuk request API
        $exceptions->shouldRenderJsonWhen(function (Request $request, \Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        // ── 405 Method Not Allowed ────────────────────────────────────────────
        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e,
            Request $request
        ) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request tidak valid. Pastikan metode HTTP yang digunakan sudah benar.',
                ], 405);
            }
            return response()->view('errors.405', [], 405);
        });

        // ── 404 Route / URL Not Found ─────────────────────────────────────────
        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e,
            Request $request
        ) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Endpoint yang dituju tidak ditemukan.',
                ], 404);
            }
            return response()->view('errors.404', [], 404);
        });

        // ── 404 Eloquent Model Not Found ──────────────────────────────────────
        $exceptions->render(function (
            \Illuminate\Database\Eloquent\ModelNotFoundException $e,
            Request $request
        ) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data yang dicari tidak ditemukan.',
                ], 404);
            }
            return response()->view('errors.404', [], 404);
        });

        // ── 401 Unauthenticated ───────────────────────────────────────────────
        $exceptions->render(function (
            \Illuminate\Auth\AuthenticationException $e,
            Request $request
        ) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesi Anda telah berakhir. Silakan login kembali.',
                ], 401);
            }
            return redirect()->guest(route('login'));
        });

        // ── 403 Unauthorized ──────────────────────────────────────────────────
        $exceptions->render(function (
            \Illuminate\Auth\Access\AuthorizationException $e,
            Request $request
        ) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk mengakses sumber daya ini.',
                ], 403);
            }
            return response()->view('errors.403', [], 403);
        });

        // ── 429 Too Many Requests ─────────────────────────────────────────────
        $exceptions->render(function (
            \Illuminate\Http\Exceptions\ThrottleRequestsException $e,
            Request $request
        ) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terlalu banyak permintaan dalam waktu singkat. Silakan tunggu sebentar lalu coba lagi.',
                ], 429);
            }
            return response()->view('errors.429', [], 429);
        });

        // ── 422 Validation Failed ─────────────────────────────────────────────
        $exceptions->render(function (
            \Illuminate\Validation\ValidationException $e,
            Request $request
        ) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data yang dikirim tidak valid.',
                    'data'    => ['errors' => $e->errors()],
                ], 422);
            }
            return back()->withErrors($e->errors())->withInput();
        });

        // ── 419 Page Expired (CSRF / Session Expired) ─────────────────────────
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Halaman expired. Silakan refresh lalu coba lagi.',
                ], 419);
            }

            return response()->view('errors.419', [], 419);
        });

        // ── 503 / 500 QueryException ──────────────────────────────────────────
        $exceptions->render(function (QueryException $e, Request $request) {
            $message = strtolower($e->getMessage());

            $isConnectionIssue =
                str_contains($message, 'sqlstate[08006]')
                || str_contains($message, 'connection to server')
                || str_contains($message, 'connection failure during authentication')
                || str_contains($message, 'could not connect to server')
                || str_contains($message, 'permission denied');

            if ($isConnectionIssue) {
                if ($request->is('api/*') || $request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Server sedang bermasalah. Silakan coba lagi beberapa saat.',
                    ], 503);
                }
                if ($request->isMethod('get')) {
                    return response()->view('errors.database-unavailable', [], 503);
                }
                return back()->withInput()->withErrors([
                    'system' => 'Koneksi database sedang bermasalah. Silakan coba lagi beberapa saat.',
                ]);
            }

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat memproses data. Silakan coba lagi.',
                ], 500);
            }
            return response()->view('errors.500', [], 500);
        });

        // ── 500 Fallback untuk semua error tidak terduga ──────────────────────
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan sistem yang tidak terduga. Silakan coba lagi.',
                ], 500);
            }
            return null; // Biarkan Laravel/Symfony menangani untuk web non-API
        });
    })->create();
