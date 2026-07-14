<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupDiagnosticsService;
use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeployCheckCommand extends Command
{
    protected $signature = 'deploy:check';

    protected $description = 'Run read-only deployment readiness checks.';

    public function handle(BackupDiagnosticsService $backupDiagnostics, Migrator $migrator): int
    {
        $failed = false;
        $failed = ! $this->check('app-key', filled(config('app.key'))) || $failed;
        $failed = ! $this->check('database', $this->databaseAvailable()) || $failed;
        $failed = ! $this->check('migrations-pending', $this->pendingMigrations($migrator) === 0) || $failed;
        $failed = ! $this->check('queue-tables', $this->requiredTablesAvailable()) || $failed;

        $backup = $backupDiagnostics->report();
        $failed = ! $this->check('backup-storage', (bool) $backup['storage_writable']) || $failed;
        $failed = ! $this->check('pg-tools', ! in_array(false, $backup['tools'], true)) || $failed;

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function check(string $name, bool $passed): bool
    {
        $this->line($name.'='.($passed ? 'ok' : 'failed'));

        return $passed;
    }

    private function databaseAvailable(): bool
    {
        try {
            DB::select('select 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function pendingMigrations(Migrator $migrator): int
    {
        try {
            $ran = $migrator->getRepository()->getRan();
            $files = $migrator->getMigrationFiles(database_path('migrations'));

            return count(array_diff(array_keys($files), $ran));
        } catch (\Throwable) {
            return PHP_INT_MAX;
        }
    }

    private function requiredTablesAvailable(): bool
    {
        try {
            foreach (['jobs', 'failed_jobs', 'generated_exports', 'cache', 'cache_locks'] as $table) {
                if (! Schema::hasTable($table)) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
