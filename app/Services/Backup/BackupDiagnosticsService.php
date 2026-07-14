<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\Process;

class BackupDiagnosticsService
{
    /** @return array<string, bool|int|string|array<string, bool>> */
    public function report(): array
    {
        $root = (new BackupFilesystem())->root();

        return [
            'enabled' => (bool) config('backup.enabled'),
            'storage_writable' => is_writable($root),
            'tools' => [
                'pg_dump' => $this->toolAvailable((string) config('backup.pg_dump_path')),
                'pg_restore' => $this->toolAvailable((string) config('backup.pg_restore_path')),
                'psql' => $this->toolAvailable((string) config('backup.psql_path')),
            ],
            'encryption_enabled' => (bool) config('backup.encryption.enabled'),
            'encryption_key_configured' => filled(config('backup.encryption.key')),
            'restore_drill_allowed' => in_array((string) config('app.env'), (array) config('backup.restore_allowed_environments'), true),
        ];
    }

    private function toolAvailable(string $binary): bool
    {
        if ($binary === '') {
            return false;
        }

        try {
            return Process::timeout(5)->run([$binary, '--version'])->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
