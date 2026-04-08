<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
