<?php

namespace App\Console\Commands;

use App\Models\DailyStockSession;
use App\Services\DailyStockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoCloseStockSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ops:auto-close-stock-sessions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Secara otomatis menutup sesi stok harian kasir yang masih terbuka dari hari sebelumnya.';

    /**
     * Execute the console command.
     */
    public function handle(DailyStockService $dailyStockService): int
    {
        $this->info('Mencari sesi stok harian yang belum ditutup...');

        // Cari semua sesi open yang tanggalnya lebih lama dari waktu cutoff (toleransi 4 jam).
        // Misalnya: pada pukul 04:00 pagi tanggal 4 Mei, now()->subHours(4)->toDateString() adalah '2026-05-04'.
        // Maka sesi dengan session_date < '2026-05-04' (yaitu '2026-05-03' dan sebelumnya) akan ditarik.
        $cutoffDate = now()->subHours(4)->toDateString();

        $openSessions = DailyStockSession::query()
            ->where('status', 'open')
            ->where('session_date', '<', $cutoffDate)
            ->get();

        if ($openSessions->isEmpty()) {
            $this->info('Tidak ada sesi stok yang perlu ditutup secara otomatis.');
            return Command::SUCCESS;
        }

        $closedCount = 0;
        $failedCount = 0;

        foreach ($openSessions as $session) {
            try {
                // 1. Rekonsiliasi pemakaian terlebih dahulu (menghitung based on transaksi).
                // Ini akan memastikan nilai $item->remaining_qty adalah (opening_qty - used_qty).
                $dailyStockService->reconcileSessionUsage($session->id);

                // 2. Tutup sesi dengan array kosong.
                // Logika di service: Jika input array kosong, akan mengambil default dari $item->remaining_qty.
                // Sehingga sisa stok otomatis akan dicatat sesuai dengan perhitungan sistem.
                // closedBy diisi dengan cashier_id nya sendiri (karena nullable pada DB namun parameter expects int).
                $dailyStockService->closeSession(
                    $session->id,
                    [],
                    $session->cashier_id,
                    'Sesi ditutup otomatis oleh sistem.'
                );

                $closedCount++;
                $this->line("Sesi #{$session->id} (Kasir: {$session->cashier_id}, Tanggal: {$session->session_date->toDateString()}) berhasil ditutup otomatis.");

            } catch (\Exception $e) {
                $failedCount++;
                $this->error("Gagal menutup sesi #{$session->id}: " . $e->getMessage());
                Log::error("ops:auto-close-stock-sessions gagal menutup sesi #{$session->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info("Proses selesai. Berhasil: {$closedCount}, Gagal: {$failedCount}.");

        return $failedCount === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
