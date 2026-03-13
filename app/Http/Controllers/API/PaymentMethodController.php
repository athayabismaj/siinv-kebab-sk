<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
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

        if ($methods->isEmpty()) {
            // Bootstraps a valid default method for fresh environments.
            PaymentMethod::query()->updateOrCreate(
                ['name' => 'Cash'],
                ['name' => 'Cash']
            );

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
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar metode pembayaran berhasil diambil.',
            'data' => [
                'user' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'role' => optional($request->user()->role)->name,
                ],
                'payment_methods' => $methods,
            ],
        ]);
    }
}
