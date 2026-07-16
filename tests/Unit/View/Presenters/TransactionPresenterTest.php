<?php

namespace Tests\Unit\View\Presenters;

use App\View\Presenters\TransactionPresenter;
use PHPUnit\Framework\TestCase;

class TransactionPresenterTest extends TestCase
{
    private TransactionPresenter $presenter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->presenter = new TransactionPresenter;
    }

    public function test_it_normalizes_transaction_status_and_preserves_the_existing_labels(): void
    {
        foreach ([
            'success' => ['success', 'Berhasil', 'success'],
            ' SUCCESS ' => ['success', 'Berhasil', 'success'],
            'void' => ['void', 'Dibatalkan', 'warning'],
            ' VOID ' => ['void', 'Dibatalkan', 'warning'],
            'payment_pending' => ['payment_pending', 'Payment Pending', 'danger'],
            '' => ['', '', 'danger'],
        ] as $input => [$key, $label, $tone]) {
            $presentation = $this->presenter->present($input, null, null);

            $this->assertSame($key, $presentation->statusKey);
            $this->assertSame($label, $presentation->statusLabel);
            $this->assertSame($tone, $presentation->tone);
        }

        $nullPresentation = $this->presenter->present(null, null, null);
        $this->assertSame('success', $nullPresentation->statusKey);
        $this->assertSame('Berhasil', $nullPresentation->statusLabel);
    }

    public function test_it_normalizes_payment_labels_without_changing_other_methods(): void
    {
        foreach ([
            'cash' => 'Tunai',
            'CASH' => 'Tunai',
            ' tunai ' => 'Tunai',
            'QRIS' => 'QRIS',
            ' Kartu Debit ' => 'Kartu Debit',
            '' => '-',
        ] as $input => $expected) {
            $this->assertSame($expected, $this->presenter->paymentLabel($input));
        }

        $this->assertSame('-', $this->presenter->paymentLabel(null));
    }

    public function test_it_maps_all_known_void_reasons_and_keeps_the_old_unknown_fallback(): void
    {
        foreach ([
            'restock' => 'Kembali ke Stok',
            'kembali_stok' => 'Kembali ke Stok',
            ' kembali stok ' => 'Kembali ke Stok',
            'waste' => 'Bahan Terbuang',
            'input_error' => 'Kesalahan Input',
            'customer_cancel' => 'Pembatalan Pesanan',
            'other' => 'Lainnya',
            'lainnya' => 'Lainnya',
        ] as $input => $expected) {
            $this->assertSame($expected, $this->presenter->voidReasonLabel($input));
        }

        $this->assertNull($this->presenter->voidReasonLabel('unknown'));
        $this->assertNull($this->presenter->voidReasonLabel(null));
    }

    public function test_it_exposes_the_existing_status_classes_for_each_tone(): void
    {
        $success = $this->presenter->present('success', 'cash', null);
        $this->assertTrue($success->isSuccess);
        $this->assertFalse($success->isVoid);
        $this->assertSame('bg-emerald-500', $success->dotClass);
        $this->assertSame('bg-emerald-500', $success->detailDotClass);
        $this->assertStringContainsString('bg-emerald-50', $success->badgeClass);
        $this->assertStringContainsString('bg-emerald-100', $success->detailBadgeClass);

        $void = $this->presenter->present('void', 'cash', 'restock');
        $this->assertFalse($void->isSuccess);
        $this->assertTrue($void->isVoid);
        $this->assertSame('bg-amber-500', $void->dotClass);
        $this->assertSame('bg-amber-500', $void->detailDotClass);
        $this->assertStringContainsString('bg-amber-50', $void->badgeClass);
        $this->assertStringContainsString('bg-amber-100', $void->detailBadgeClass);

        $unknown = $this->presenter->present('failed', 'cash', null);
        $this->assertFalse($unknown->isSuccess);
        $this->assertFalse($unknown->isVoid);
        $this->assertSame('bg-rose-500', $unknown->dotClass);
        $this->assertSame('bg-red-500', $unknown->detailDotClass);
        $this->assertStringContainsString('bg-rose-50', $unknown->badgeClass);
        $this->assertStringContainsString('bg-red-100', $unknown->detailBadgeClass);
        $this->assertStringContainsString('bg-red-500/5', $unknown->haloClass);
        $this->assertStringContainsString('bg-red-50', $unknown->iconWrapClass);
        $this->assertStringContainsString('text-red-600', $unknown->iconClass);
    }
}
