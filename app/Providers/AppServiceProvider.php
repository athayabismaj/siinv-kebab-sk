<?php

namespace App\Providers;

use App\Models\DailyStockSession;
use App\Models\Transaction;
use App\Policies\DailyStockSessionPolicy;
use App\Policies\TransactionPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Contracts\Services\VoidTransactionServiceInterface::class,
            \App\Services\VoidTransactionService::class
        );

        $this->app->bind(
            \App\Contracts\Services\CloseSessionServiceInterface::class,
            \App\Services\CloseSessionService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ((bool) env('APP_FORCE_HTTPS', env('APP_ENV') === 'production')) {
            URL::forceScheme('https');
        }

        Gate::before(function ($user, $ability) {
            if (strtolower(optional(optional($user)->role)->name) === 'developer') {
                return true;
            }
        });

        Gate::policy(DailyStockSession::class, DailyStockSessionPolicy::class);
        Gate::policy(Transaction::class, TransactionPolicy::class);

        RateLimiter::for('auth-login', function (Request $request) {
            $identifier = strtolower((string) ($request->input('username') ?? $request->input('email') ?? 'guest'));
            $ip = $request->ip();

            return [
                Limit::perMinute(8)->by('auth-login:ip:' . $ip),
                Limit::perMinute(5)->by('auth-login:id:' . $identifier),
            ];
        });

        RateLimiter::for('auth-forgot-password', function (Request $request) {
            $email = strtolower((string) ($request->input('email') ?? 'guest'));
            $ip = $request->ip();

            return [
                Limit::perMinutes(10, 4)->by('auth-forgot:ip:' . $ip),
                Limit::perMinutes(10, 3)->by('auth-forgot:email:' . $email),
            ];
        });

        RateLimiter::for('auth-reset-password', function (Request $request) {
            $identifier = strtolower((string) ($request->input('email') ?? $request->ip()));

            return Limit::perMinutes(10, 8)->by('auth-reset:' . $identifier);
        });

        RateLimiter::for('api-auth', function (Request $request) {
            $keyBase = $request->ip();

            return Limit::perMinute(20)->by('api-auth:' . $keyBase);
        });

        RateLimiter::for('api-read-role-aware', function (Request $request) {
            $user = $request->user();
            $role = strtolower((string) optional(optional($user)->role)->name);
            $keyBase = (string) (optional($user)->id ?? $request->ip());

            // Kasir dibuat longgar karena jalur operasional.
            if ($role === 'kasir') {
                return Limit::perMinute(180)->by('api-read:kasir:' . $keyBase);
            }

            // Admin/owner lebih ketat agar tidak ganggu endpoint kasir.
            if (in_array($role, ['admin', 'owner'], true)) {
                return Limit::perMinute(60)->by('api-read:privileged:' . $keyBase);
            }

            return Limit::perMinute(90)->by('api-read:default:' . $keyBase);
        });

        RateLimiter::for('api-checkout-role-aware', function (Request $request) {
            $user = $request->user();
            $role = strtolower((string) optional(optional($user)->role)->name);
            $keyBase = (string) (optional($user)->id ?? $request->ip());

            if ($role === 'kasir') {
                return Limit::perMinute(90)->by('api-checkout:kasir:' . $keyBase);
            }

            if (in_array($role, ['admin', 'owner'], true)) {
                return Limit::perMinute(20)->by('api-checkout:privileged:' . $keyBase);
            }

            return Limit::perMinute(45)->by('api-checkout:default:' . $keyBase);
        });

        RateLimiter::for('web-heavy-role-aware', function (Request $request) {
            $user = $request->user();
            $role = strtolower((string) optional(optional($user)->role)->name);
            $keyBase = (string) (optional($user)->id ?? $request->ip());

            if (in_array($role, ['admin', 'owner'], true)) {
                return Limit::perMinute(12)->by('web-heavy:privileged:' . $keyBase);
            }

            return Limit::perMinute(20)->by('web-heavy:default:' . $keyBase);
        });

        // Bagikan jumlah stok rendah dan habis ke partials.header untuk notifikasi
        \Illuminate\Support\Facades\View::composer('partials.header', function (\Illuminate\View\View $view) {
            // Hanya jalankan query jika user terautentikasi dan memiliki role admin/owner
            $user = auth()->user();
            $role = strtolower((string) optional(optional($user)->role)->name);

            if (in_array($role, ['admin', 'owner'], true)) {
                $counts = \Illuminate\Support\Facades\Cache::remember(
                    \App\Support\AdminCache::key('dashboard', 'stock_notifications_count'),
                    now()->addMinutes(5),
                    function () {
                        $outOfStock = \App\Models\Ingredient::query()
                            ->where('stock', '<=', 0)
                            ->count();
                            
                        $lowStock = \App\Models\Ingredient::query()
                            ->where('stock', '>', 0)
                            ->whereColumn('stock', '<=', 'minimum_stock')
                            ->count();
                            
                        return [
                            'outOfStock' => $outOfStock,
                            'lowStock' => $lowStock,
                        ];
                    }
                );
                $view->with('outOfStockCount', $counts['outOfStock']);
                $view->with('lowStockCount', $counts['lowStock']);
            } else {
                $view->with('outOfStockCount', 0);
                $view->with('lowStockCount', 0);
            }
        });
    }
}
