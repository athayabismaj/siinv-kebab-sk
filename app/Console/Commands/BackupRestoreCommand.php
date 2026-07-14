<?php

namespace App\Console\Commands;

use App\Models\BackupHistory;
use App\Services\Backup\PostgreSqlRestoreService;
use Illuminate\Console\Command;

class BackupRestoreCommand extends Command
{
    protected $signature = 'backup:restore {backup : Backup history ID to verify through a disposable restore drill}';

    protected $description = 'Verify one trusted backup with a disposable PostgreSQL restore drill.';

    public function handle(PostgreSqlRestoreService $restoreService): int
    {
        $backup = BackupHistory::query()->findOrFail((int) $this->argument('backup'));

        try {
            $restoreService->drill((string) $backup->file_path);
            $this->info('Restore drill completed. The application database was not changed.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('Restore drill failed. Review the server log for safe diagnostic details.');

            return self::FAILURE;
        }
    }
}
