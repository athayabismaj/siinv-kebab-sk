<?php

namespace Tests\Unit\View\Presenters;

use App\View\Presenters\StockSessionPresenter;
use PHPUnit\Framework\TestCase;

class StockSessionPresenterTest extends TestCase
{
    public function test_it_preserves_dashboard_session_tones_and_label_fallback(): void
    {
        $presenter = new StockSessionPresenter;

        $open = $presenter->present('open', 'Masih Berjalan');
        $this->assertSame('Masih Berjalan', $open->label);
        $this->assertSame('bg-amber-500', $open->dotClass);
        $this->assertStringContainsString('text-amber-700', $open->textClass);
        $this->assertStringContainsString('border-amber-200', $open->badgeClass);

        $closed = $presenter->present('closed', 'Sudah Ditutup');
        $this->assertSame('Sudah Ditutup', $closed->label);
        $this->assertSame('bg-emerald-500', $closed->dotClass);
        $this->assertStringContainsString('text-emerald-700', $closed->textClass);
        $this->assertStringContainsString('border-emerald-200', $closed->badgeClass);

        $fallback = $presenter->present(null, null);
        $this->assertSame('Belum Dibuka', $fallback->label);
        $this->assertSame('bg-slate-400', $fallback->dotClass);
        $this->assertStringContainsString('text-slate-700', $fallback->textClass);
        $this->assertStringContainsString('border-slate-200', $fallback->badgeClass);
    }
}
