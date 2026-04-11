<?php

namespace App\Providers;

use App\Models\DailyStockSession;
use App\Policies\DailyStockSessionPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(DailyStockSession::class, DailyStockSessionPolicy::class);

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
    }
}
