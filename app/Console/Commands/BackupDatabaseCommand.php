<?php

namespace App\Console\Commands;

use App\Models\BackupHistory;
use App\Services\Backup\PostgreSqlBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'backup:database {--type=scheduled : Tipe backup (scheduled/manual)}';

    protected $description = 'Create a verified PostgreSQL backup in private storage.';

    public function handle(PostgreSqlBackupService $backups): int
    {
        $type = (string) $this->option('type');
        $this->info("Memulai backup database [{$type}]...");

        try {
            $backup = $backups->create($type);

            BackupHistory::query()->create([
                'file_name' => basename($backup['file_path']),
                'file_path' => $backup['file_path'],
                'file_size' => $backup['manifest']['size_bytes'],
                'status' => 'success',
            ]);

            $this->info('Backup berhasil dibuat dan diverifikasi.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->recordFailure($type);

            Log::warning('Scheduled database backup failed.', [
                'operation' => 'backup',
                'type' => $type,
                'exception' => $exception::class,
            ]);
            $this->error('Backup gagal. Detail teknis sudah dicatat di log server.');

            return self::FAILURE;
        }
    }

    private function recordFailure(string $type): void
    {
        try {
            BackupHistory::query()->create([
                'file_name' => 'failed-'.now()->format('YmdHis'),
                'status' => 'failed',
                'error_message' => 'Backup '.$type.' gagal. Detail teknis sudah dicatat di log server.',
            ]);
        } catch (\Throwable) {
            // The structured log in handle() remains available if the database is unavailable.
        }
    }
}
