<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\CloseDailyStockSessionRequest;
use App\Models\DailyStockSession;
use App\Services\DailyStockService;
use App\Support\BranchScope;
use App\Support\IngredientUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DailyStockController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly DailyStockService $dailyStockService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->unauthorizedResponse();
        }

        $session = $this->openSessionForCashier(
            (int) $user->id,
            BranchScope::userBranchId($user)
        );

        if (! $session) {
            return $this->successResponse('Sesi stok harian belum dibuka oleh admin hari ini.', [
                'session_id' => null,
                'items' => [],
            ]);
        }

        $items = $session->items->map(function ($item) {
            $ingredient = $item->ingredient;
            if (! $ingredient) {
                return null;
            }

            $baseValue = (float) $item->opening_qty;
            $remainingBase = (float) $item->remaining_qty;
            $unit = strtolower((string) $ingredient->display_unit);

            // Konversi base unit (misal gram) ke display unit (kg) jika perlu
            if (in_array($unit, ['kg', 'l'], true)) {
                $qty = round($baseValue / 1000, 2);
                $remainingQty = round($remainingBase / 1000, 2);
            } else {
                $qty = round($baseValue, 2);
                $remainingQty = round($remainingBase, 2);
            }

            return [
                'ingredient_id' => $ingredient->id,
                'name' => $ingredient->name,
                'qty' => $qty,
                'remaining_qty' => $remainingQty,
                'unit' => $unit,
                'display_qty' => (string) $qty,
            ];
        })->filter()->values();

        return $this->successResponse('Berhasil mengambil stok bahan harian', [
            'session_id' => $session->id,
            'items' => $items,
        ]);
    }

    public function closeSession(CloseDailyStockSessionRequest $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->unauthorizedResponse();
        }

        $session = $this->openSessionForCashier(
            (int) $user->id,
            BranchScope::userBranchId($user)
        );

        if (! $session) {
            return $this->errorResponse('Tidak ada sesi stok harian yang aktif untuk ditutup.', null, 404);
        }

        // Konversi display value dari Android ke base unit sebelum dikirim ke service.
        // Dukungan dua format payload:
        // 1) map: remaining[ingredient_id] = value
        // 2) list object: remaining[] = { ingredient_id, remaining_qty|remaining|qty }
        $remainingByIngredient = [];
        $remainingPayload = $request->input('remaining', []);
        $isListPayload = array_is_list($remainingPayload);

        foreach ($remainingPayload as $key => $rawValue) {
            if ($isListPayload) {
                if (! is_array($rawValue)) {
                    continue;
                }

                $ingredientId = (int) ($rawValue['ingredient_id'] ?? $rawValue['id'] ?? 0);
                $displayValue = $rawValue['remaining_qty'] ?? $rawValue['remaining'] ?? $rawValue['qty'] ?? null;
            } else {
                $ingredientId = (int) $key;
                $displayValue = $rawValue;
            }

            $displayNumeric = $this->parseDisplayNumeric($displayValue);
            if ($ingredientId <= 0 || $displayNumeric === null) {
                continue;
            }

            $displayNumeric = max(0, round($displayNumeric, 2));

            $item = $session->items->firstWhere('ingredient_id', $ingredientId);
            if (! $item || ! $item->ingredient) {
                continue;
            }

            $ingredient = $item->ingredient;
            $unit = strtolower((string) $ingredient->display_unit);

            // Kasir menginput dalam display unit (kg, l, pcs),
            // DailyStockService mengharapkan nilai base unit (g, ml, pcs)
            if (in_array($unit, ['kg', 'l'], true)) {
                $remainingByIngredient[$ingredientId] = round($displayNumeric * 1000, 2);
            } else {
                $remainingByIngredient[$ingredientId] = $displayNumeric;
            }
        }

        if (empty($remainingByIngredient)) {
            return $this->errorResponse('Format data sisa stok tidak valid. Silakan perbarui aplikasi kasir atau sinkron ulang data.', null, 422);
        }

        try {
            $closedSession = $this->dailyStockService->closeSession(
                $session->id,
                $remainingByIngredient,
                $user->id,
                $request->input('notes'),
                BranchScope::userBranchId($user)
            );

            return $this->successResponse('Sesi stok harian berhasil ditutup.', [
                'session_id' => $closedSession->id,
                'status' => $closedSession->status,
            ]);
        } catch (\RuntimeException $e) {
            Log::warning('Gagal menutup sesi stok harian via API.', [
                'user_id' => $user->id,
                'session_id' => $session->id,
                'exception' => get_class($e),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Sesi stok harian gagal ditutup. Periksa data sisa stok lalu coba lagi.', null, 422);
        } catch (\Throwable $e) {
            Log::error('Error server saat menutup sesi stok harian via API.', [
                'user_id' => $user->id,
                'session_id' => $session->id,
                'exception' => get_class($e),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Gagal menutup sesi stok harian. Silakan coba lagi.', null, 500);
        }
    }

    private function parseDisplayNumeric(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);
        if ($normalized === '') {
            return null;
        }

        // Support angka dari mobile dengan format lokal, mis. "0,25" atau "1.250,50".
        $normalized = str_replace(' ', '', $normalized);
        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            // Asumsikan format lokal Indonesia: titik ribuan, koma desimal.
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } elseif (str_contains($normalized, ',')) {
            // Koma sebagai desimal.
            $normalized = str_replace(',', '.', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function openSessionForCashier(int $cashierId, ?int $branchId): ?DailyStockSession
    {
        return DailyStockSession::query()
            ->with(['items.ingredient'])
            ->where('cashier_id', $cashierId)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->whereRaw("LOWER(TRIM(status)) = 'open'")
            ->latest('session_date')
            ->latest('id')
            ->first();
    }
}
