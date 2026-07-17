<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class PostgreSqlBackupService
{
    public function __construct(
        private readonly BackupFilesystem $filesystem,
        private readonly BackupManifestService $manifests,
        private readonly PostgreSqlProcessRunner $processes,
    ) {
    }

    /** @return array{backup_id:string,file_path:string,manifest_path:string,manifest:array<string,mixed>} */
    public function create(string $type = 'manual'): array
    {
        if (! config('backup.enabled')) {
            throw new RuntimeException('Backup is disabled.');
        }

        if (config('backup.encryption.enabled')) {
            if (blank(config('backup.encryption.key'))) {
                throw new RuntimeException('Backup encryption requires a configured key.');
            }

            throw new RuntimeException('Encrypted backups are not available until an approved encryption implementation is configured.');
        }

        $connection = $this->connection();
        $backupId = (string) Str::uuid();
        $temporaryDirectory = $this->filesystem->temporaryDirectory($backupId);
        $artifactDirectory = $this->filesystem->artifactDirectory($backupId);
        $backupFileName = 'SK-'.now()->locale('id')->translatedFormat('d-F-Y').'.dump';
        $temporaryDump = $temporaryDirectory.DIRECTORY_SEPARATOR.$backupFileName;
        $startedAt = microtime(true);
        $processDiagnostics = [
            'process_exit_code' => null,
            'process_error' => null,
            'process_output' => null,
            'artifact_state' => null,
        ];

        try {
            File::ensureDirectoryExists($temporaryDirectory);

            // Keep the output location absolute and ready before pg_dump starts.
            File::ensureDirectoryExists(dirname($temporaryDump));

            $result = $this->processes->run(
                $this->dumpCommand($connection, $temporaryDump),
                ['PGPASSWORD' => (string) $connection['password']],
            );

            $artifactState = $this->artifactState($temporaryDump);

            if (! $result->successful() || $artifactState !== 'ready') {
                $processDiagnostics = $this->processDiagnostics($result, $artifactState);

                throw new RuntimeException('Database backup process failed.');
            }

            $manifest = [
                'backup_id' => $backupId,
                'created_at' => now()->toIso8601String(),
                'completed_at' => now()->toIso8601String(),
                'database_driver' => 'pgsql',
                'backup_format' => 'custom',
                'compressed' => true,
                'encrypted' => false,
                'checksum_algorithm' => 'sha256',
                'checksum' => hash_file('sha256', $temporaryDump),
                'size_bytes' => filesize($temporaryDump),
                'application_version' => app()->version(),
                'migration_state' => ['available' => false],
                'status' => 'success',
                'type' => $type,
            ];
            $temporaryManifest = $this->manifests->write($temporaryDump, $manifest);
            $this->filesystem->publish($temporaryDirectory, $artifactDirectory);

            $filePath = $artifactDirectory.DIRECTORY_SEPARATOR.basename($temporaryDump);
            $manifestPath = $artifactDirectory.DIRECTORY_SEPARATOR.basename($temporaryManifest);

            Log::info('Database backup completed.', [
                'operation' => 'backup',
                'backup_id' => $backupId,
                'type' => $type,
                'result' => 'success',
                'size_bytes' => $manifest['size_bytes'],
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            ]);

            return [
                'backup_id' => $backupId,
                'file_path' => $filePath,
                'manifest_path' => $manifestPath,
                'manifest' => $manifest,
            ];
        } catch (\Throwable $exception) {
            if ($processDiagnostics['process_error'] === null) {
                $processDiagnostics['process_error'] = $this->sanitizeProcessMessage($exception->getMessage());
            }

            $this->filesystem->deleteDirectory($temporaryDirectory);

            Log::warning('Database backup failed.', [
                'operation' => 'backup',
                'type' => $type,
                'result' => 'failed',
                'exception' => $exception::class,
                'process_exit_code' => $processDiagnostics['process_exit_code'],
                'process_error' => $processDiagnostics['process_error'],
                'process_output' => $processDiagnostics['process_output'],
                'artifact_state' => $processDiagnostics['artifact_state'],
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            ]);

            throw $exception;
        }
    }

    /** @return array<string, mixed> */
    private function connection(): array
    {
        $connection = config('database.connections.'.config('backup.database_connection'));

        if (! is_array($connection) || ($connection['driver'] ?? null) !== 'pgsql') {
            throw new RuntimeException('A PostgreSQL backup connection is required.');
        }

        foreach (['host', 'port', 'database', 'username', 'password'] as $key) {
            if (! array_key_exists($key, $connection) || $connection[$key] === null || $connection[$key] === '') {
                throw new RuntimeException('The backup connection configuration is incomplete.');
            }
        }

        return $connection;
    }

    /** @param array<string, mixed> $connection @return array<int, string> */
    private function dumpCommand(array $connection, string $temporaryDump): array
    {
        return [
            (string) config('backup.pg_dump_path'),
            '--format=custom',
            '--no-owner',
            '--no-privileges',
            '--file', $temporaryDump,
            '--host', (string) $connection['host'],
            '--port', (string) $connection['port'],
            '--username', (string) $connection['username'],
            (string) $connection['database'],
        ];
    }

    private function artifactState(string $temporaryDump): string
    {
        if (! is_file($temporaryDump)) {
            return 'missing';
        }

        return filesize($temporaryDump) > 0 ? 'ready' : 'empty';
    }

    /** @return array{process_exit_code:int|null,process_error:string|null,process_output:string|null,artifact_state:string} */
    private function processDiagnostics(mixed $result, string $artifactState): array
    {
        return [
            'process_exit_code' => method_exists($result, 'exitCode') ? $result->exitCode() : null,
            'process_error' => $this->sanitizeProcessMessage(
                method_exists($result, 'errorOutput') ? (string) $result->errorOutput() : '',
            ),
            'process_output' => $this->sanitizeProcessMessage(
                method_exists($result, 'output') ? (string) $result->output() : '',
            ),
            'artifact_state' => $artifactState,
        ];
    }

    private function sanitizeProcessMessage(string $message): ?string
    {
        $message = preg_replace('/\b(PGPASSWORD|password)\s*=\s*(?:"[^"]*"|\'[^\']*\'|\S+)/i', '$1=[redacted]', $message) ?? '';
        $message = preg_replace('#(postgres(?:ql)?://[^:\s/]+:)[^@\s/]+(@)#i', '$1[redacted]$2', $message) ?? '';
        $message = preg_replace('/\s+/', ' ', trim($message)) ?? '';

        return $message === '' ? null : Str::limit($message, 1000);
    }
}
