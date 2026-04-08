<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Support\AdminCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $methods = Cache::remember(
            AdminCache::key('payment_methods', 'api:list'),
            now()->addSeconds(120),
            function () {
                $methods = PaymentMethod::query()
                    ->whereNull('deleted_at')
                    ->select('id', 'name')
                    ->orderBy('name')
                    ->get()
                    ->map(fn ($method) => [
                        'id' => $method->id,
                        'name' => $method->name,
                    ])
                    ->values();

                if ($methods->isNotEmpty()) {
                    return $methods;
                }

                PaymentMethod::query()->updateOrCreate(
                    ['name' => 'Cash'],
                    ['name' => 'Cash']
                );

                return PaymentMethod::query()
                    ->whereNull('deleted_at')
                    ->select('id', 'name')
                    ->orderBy('name')
                    ->get()
                    ->map(fn ($method) => [
                        'id' => $method->id,
                        'name' => $method->name,
                    ])
                    ->values();
            }
        );

        return response()->json([
            'success' => true,
            'message' => 'Daftar metode pembayaran berhasil diambil.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => optional($user->role)->name,
                ],
                'payment_methods' => $methods,
            ],
        ]);
    }
}

