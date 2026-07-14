<?php

use App\Actions\DailyStock\CloseDailyStockSessionAction;
use App\Actions\DailyStock\OpenDailyStockSessionAction;
use App\Actions\DailyStock\TransferToDailyStockAction;
use App\Actions\Inventory\AdjustInventoryStockAction;
use App\Actions\Inventory\RestockInventoryStockAction;
use App\Actions\Sales\CheckoutTransactionAction;
use App\Actions\Sales\VoidTransactionAction;
use App\DTOs\VoidTransactionRequestDto;
use App\Enums\VoidInventoryActionEnum;
use App\Models\Branch;
use App\Models\User;
use App\Services\Analytics\DailySalesSummaryService;

require dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

$app = require dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

[$script, $scenario, $encodedPayload] = array_pad($argv, 3, null);
$payload = json_decode(base64_decode((string) $encodedPayload, true), true, 512, JSON_THROW_ON_ERROR);

try {
    $result = match ($scenario) {
        'checkout' => app(CheckoutTransactionAction::class)->execute([
            'payment_method_id' => (int) $payload['payment_method_id'],
            'paid_amount' => (float) $payload['paid_amount'],
            'items' => [[
                'variant_id' => (int) $payload['variant_id'],
                'qty' => (int) $payload['qty'],
            ]],
        ], (int) $payload['cashier_id']),
        'open-session' => app(OpenDailyStockSessionAction::class)->execute(
            (string) $payload['session_date'],
            (int) $payload['cashier_id'],
            (int) $payload['opened_by'],
            null,
            (int) $payload['branch_id'],
        )->only(['id', 'status']),
        'restock' => app(RestockInventoryStockAction::class)->execute(
            (int) $payload['ingredient_id'],
            (float) $payload['quantity'],
            'pcs',
            'Fase 5B concurrency',
            (int) $payload['branch_id'],
        )->only(['id', 'stock']),
        'adjust' => app(AdjustInventoryStockAction::class)->execute(
            (int) $payload['ingredient_id'],
            (float) $payload['new_stock'],
            'pcs',
            'Fase 5B concurrency',
            (int) $payload['branch_id'],
        )?->only(['id', 'stock']),
        'transfer' => app(TransferToDailyStockAction::class)->executeBatch(
            (int) $payload['session_id'],
            [(int) $payload['ingredient_id'] => ['qty' => (float) $payload['quantity'], 'note' => null]],
            (int) $payload['actor_id'],
            (int) $payload['branch_id'],
        ),
        'close-session' => app(CloseDailyStockSessionAction::class)->execute(
            (int) $payload['session_id'],
            [(int) $payload['ingredient_id'] => (float) $payload['remaining_qty']],
            (int) $payload['actor_id'],
            null,
            (int) $payload['branch_id'],
        )->only(['id', 'status']),
        'void' => app(VoidTransactionAction::class)->execute(new VoidTransactionRequestDto(
            (int) $payload['transaction_id'],
            (int) $payload['session_id'],
            User::query()->findOrFail((int) $payload['actor_id']),
            (string) $payload['idempotency_key'],
            VoidInventoryActionEnum::RESTOCK,
        )),
        'rebuild-summary' => app(DailySalesSummaryService::class)->rebuildForDate(
            Branch::query()->findOrFail((int) $payload['branch_id']),
            now(),
        ),
        default => throw new RuntimeException("Unsupported Fase 5B worker scenario: {$scenario}"),
    };

    echo json_encode(['ok' => true, 'result' => $result], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    echo json_encode([
        'ok' => false,
        'error' => get_class($exception),
        'message' => $exception->getMessage(),
    ], JSON_THROW_ON_ERROR);
}
