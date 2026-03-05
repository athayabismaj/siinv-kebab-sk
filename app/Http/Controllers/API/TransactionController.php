<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MenuVariant;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:30,1')->only('store');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_method_id' => 'required|integer|exists:payment_methods,id',
            'paid_amount' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer|exists:menu_variants,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.price' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $userId = (int) auth()->id();
            if ($userId <= 0) {
                throw new \RuntimeException('User tidak terautentikasi.');
            }

            $now = now();
            $lineItems = [];
            $totalAmount = 0.0;

            foreach ($validated['items'] as $item) {
                $variant = MenuVariant::query()->findOrFail((int) $item['variant_id']);

                $qty = (float) $item['qty'];
                $price = array_key_exists('price', $item) && $item['price'] !== null
                    ? (float) $item['price']
                    : (float) $variant->price;

                $subtotal = $price * $qty;
                $totalAmount += $subtotal;

                $lineItems[] = [
                    'variant_id' => (int) $item['variant_id'],
                    'menu_id' => (int) $variant->menu_id,
                    'qty' => $qty,
                    'price' => $price,
                    'subtotal' => $subtotal,
                ];
            }

            $paidAmount = (float) $validated['paid_amount'];
            if ($paidAmount < $totalAmount) {
                throw new \RuntimeException('Nominal pembayaran kurang dari total transaksi.');
            }

            $transactionId = DB::table('transactions')->insertGetId([
                'transaction_code' => $this->generateTransactionCode(),
                'user_id' => $userId,
                'total_amount' => $totalAmount,
                'payment_method_id' => (int) $validated['payment_method_id'],
                'paid_amount' => $paidAmount,
                'change_amount' => $paidAmount - $totalAmount,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($lineItems as $line) {
                DB::table('transaction_details')->insert([
                    'transaction_id' => $transactionId,
                    'menu_id' => $line['menu_id'],
                    'quantity' => $line['qty'],
                    'price' => $line['price'],
                    'subtotal' => $line['subtotal'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                StockService::deductStock(
                    $line['variant_id'],
                    $line['qty'],
                    $transactionId,
                    $validated['note'] ?? null
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil',
                'data' => [
                    'transaction_id' => $transactionId,
                    'total_amount' => round($totalAmount, 2),
                    'paid_amount' => round($paidAmount, 2),
                    'change_amount' => round($paidAmount - $totalAmount, 2),
                ],
            ], 201);
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Gagal memproses transaksi kasir.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $isValidation = $e instanceof \Illuminate\Validation\ValidationException;

            return response()->json([
                'success' => false,
                'message' => $isValidation
                    ? 'Validasi transaksi tidak valid.'
                    : 'Transaksi gagal diproses. Silakan coba lagi.',
            ], $isValidation ? 422 : 500);
        }
    }

    private function generateTransactionCode(): string
    {
        do {
            $code = 'TRX-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
        } while (DB::table('transactions')->where('transaction_code', $code)->exists());

        return $code;
    }
}
