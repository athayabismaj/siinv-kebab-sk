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
     * Hapus file backup yang lebih tua dari $days hari.
     */
    private function cleanOldBackups(int $days): void
    {
        $cutoff = Carbon::now()->subDays($days);

        $oldBackups = BackupHistory::where('status', 'success')
            ->where('created_at', '<', $cutoff)
            ->get();

        foreach ($oldBackups as $backup) {
            if ($backup->file_path && File::exists($backup->file_path)) {
                File::delete($backup->file_path);
                $this->line("  Deleted old file: {$backup->file_name}");
            }
            $backup->update(['file_path' => null, 'file_size' => null]);
        }

        if ($oldBackups->count() > 0) {
            $this->info("Membersihkan {$oldBackups->count()} file backup lama (>{$days} hari).");
        }
    }
}
