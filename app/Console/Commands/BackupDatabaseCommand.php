<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BackupHistory;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class BackupDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database {--type=scheduled : Tipe backup (scheduled/manual)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup database PostgreSQL secara otomatis (pg_dump) dan mencatatnya ke riwayat.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $this->info("Memulai proses backup database [{$type}]...");

        try {
            $dbName = env('DB_DATABASE');
            $dbUser = env('DB_USERNAME');
            $dbPassword = env('DB_PASSWORD');
            $dbHost = env('DB_HOST', '127.0.0.1');
            $dbPort = env('DB_PORT', '5432');

            $filename = 'backup-' . $dbName . '-' . Carbon::now()->format('Y-m-d-H-i-s') . '.sql';
            $backupPath = storage_path('app/backups/');

            if (!File::exists($backupPath)) {
                File::makeDirectory($backupPath, 0755, true);
            }

            $filePath = $backupPath . $filename;

            putenv("PGPASSWORD=" . $dbPassword);

            $pgDumpPath = env('PG_DUMP_PATH', 'pg_dump');

            $command = "\"{$pgDumpPath}\" -h {$dbHost} -p {$dbPort} -U {$dbUser} -F c -b -v -f \"{$filePath}\" {$dbName}";

            exec($command . ' 2>&1', $output, $returnVar);
            $outputStr = implode("\n", $output);

            putenv("PGPASSWORD");

            if ($returnVar !== 0) {
                BackupHistory::create([
                    'file_name' => $filename,
                    'status' => 'failed',
                    'error_message' => $outputStr,
                ]);

                $this->error("Backup gagal: {$outputStr}");
                return Command::FAILURE;
            }

            BackupHistory::create([
                'file_name' => $filename,
                'file_path' => $filePath,
                'file_size' => File::size($filePath),
                'status' => 'success',
            ]);

            $this->info("Backup berhasil: {$filename}");

            // Bersihkan file backup lama (lebih dari 30 hari)
            $this->cleanOldBackups(30);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            BackupHistory::create([
                'file_name' => 'Failed-' . Carbon::now()->format('Y-m-d-H-i-s'),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $this->error("Exception: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Hapus file backup yang lama dengan strategi Grandfather-Father-Son (GFS).
     * Mempertahankan:
     * - 7 hari terakhir (Daily)
     * - 1 backup per minggu (Weekly)
     * - 1 backup per bulan (Monthly)
     */
    private function cleanOldBackups(int $days): void
    {
        $this->info("Menjalankan GFS Backup Retention Strategy...");

        // Ambil semua backup otomatis yang berhasil
        $allBackups = BackupHistory::where('status', 'success')
            ->whereNull('user_id')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($allBackups->isEmpty()) {
            return;
        }

        $now = Carbon::now();
        $keepIds = [];

        // 1. Cari Backup Bulanan (End of Month)
        // Group by Year-Month
        $monthlyGroups = $allBackups->groupBy(function($backup) {
            return Carbon::parse($backup->created_at)->format('Y-m');
        });

        foreach ($monthlyGroups as $month => $backupsInMonth) {
            // Karena sudah orderBy desc, first() adalah backup terbaru di bulan itu (mendekati akhir bulan)
            $keepIds[] = $backupsInMonth->first()->id;
        }

        // 2. Cari Backup Mingguan (End of Week)
        // Group by Year-Week
        $weeklyGroups = $allBackups->groupBy(function($backup) {
            return Carbon::parse($backup->created_at)->format('o-W'); // 'o' is ISO year, 'W' is ISO week number
        });

        foreach ($weeklyGroups as $week => $backupsInWeek) {
            // Ambil backup terbaru di minggu itu (mendekati akhir minggu/Minggu malam)
            $keepIds[] = $backupsInWeek->first()->id;
        }

        // 3. Cari Backup Harian (Daily) untuk 7 hari terakhir
        $sevenDaysAgo = $now->copy()->subDays(7);
        foreach ($allBackups as $backup) {
            if (Carbon::parse($backup->created_at)->isAfter($sevenDaysAgo)) {
                $keepIds[] = $backup->id;
            }
        }

        // Hapus duplikat ID
        $keepIds = array_unique($keepIds);

        // Ambil backup yang TIDAK dipertahankan (akan dikonsolidasikan/dihapus)
        $toDelete = BackupHistory::where('status', 'success')
            ->whereNull('user_id')
            ->whereNotIn('id', $keepIds)
            ->get();

        $deletedCount = 0;
        foreach ($toDelete as $backup) {
            if ($backup->file_path && File::exists($backup->file_path)) {
                File::delete($backup->file_path);
                $this->line("  Deleted consolidated backup: {$backup->file_name}");
            }
            // Hapus record dari database agar riwayat tetap bersih hanya menyisakan tier GFS
            $backup->delete();
            $deletedCount++;
        }

        if ($deletedCount > 0) {
            $this->info("Membersihkan {$deletedCount} file backup lama (Konsolidasi GFS).");
        } else {
            $this->info("Tidak ada file backup yang perlu dikonsolidasikan.");
        }
    }
}
