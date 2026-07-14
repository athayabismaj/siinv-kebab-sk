<?php

namespace App\Services\Backup;

use Illuminate\Support\Str;
use RuntimeException;

class PostgreSqlRestoreService
{
    public function __construct(
        private readonly BackupFilesystem $filesystem,
        private readonly BackupManifestService $manifests,
        private readonly PostgreSqlProcessRunner $processes,
    ) {
    }

    /** @return array{target_database:string,manifest:array<string,mixed>} */
    public function restoreTo(string $artifactPath, string $targetDatabase): array
    {
        $connection = $this->connection();
        $this->assertRestoreEnvironment();
        $this->assertTargetDatabase($targetDatabase, $connection);
        $manifest = $this->manifests->verify($artifactPath);

        if (($manifest['database_driver'] ?? null) !== 'pgsql' || ($manifest['backup_format'] ?? null) !== 'custom' || ($manifest['encrypted'] ?? false) === true) {
            throw new RuntimeException('Backup artifact is not supported for restore.');
        }

        $environment = ['PGPASSWORD' => (string) $connection['password']];
        $this->runOrFail([
            (string) config('backup.psql_path'),
            '--host', (string) $connection['host'],
            '--port', (string) $connection['port'],
            '--username', (string) $connection['username'],
            '--dbname', (string) config('backup.maintenance_database'),
            '--set', 'ON_ERROR_STOP=1',
            '--command', 'CREATE DATABASE '.$this->quoteIdentifier($targetDatabase).' OWNER '.$this->quoteIdentifier((string) $connection['username']),
        ], $environment);

        $this->runOrFail([
            (string) config('backup.pg_restore_path'),
            '--exit-on-error',
            '--no-owner',
            '--no-privileges',
            '--host', (string) $connection['host'],
            '--port', (string) $connection['port'],
            '--username', (string) $connection['username'],
            '--dbname', $targetDatabase,
            $artifactPath,
        ], $environment);

        $this->runOrFail([
            (string) config('backup.psql_path'),
            '--host', (string) $connection['host'],
            '--port', (string) $connection['port'],
            '--username', (string) $connection['username'],
            '--dbname', $targetDatabase,
            '--set', 'ON_ERROR_STOP=1',
            '--command', "SELECT to_regclass('public.migrations') IS NOT NULL",
        ], $environment);

        return [
            'target_database' => $targetDatabase,
            'manifest' => $manifest,
        ];
    }

    /** @return array{target_database:string,manifest:array<string,mixed>} */
    public function drill(string $artifactPath): array
    {
        $targetDatabase = (string) config('backup.restore_database_prefix').Str::lower(Str::random(20));
        $result = $this->restoreTo($artifactPath, $targetDatabase);

        $this->dropDisposableDatabase($targetDatabase);

        return $result;
    }

    public function dropDisposableDatabase(string $targetDatabase): void
    {
        $connection = $this->connection();
        $this->assertRestoreEnvironment();
        $this->assertTargetDatabase($targetDatabase, $connection);

        $this->runOrFail([
            (string) config('backup.psql_path'),
            '--host', (string) $connection['host'],
            '--port', (string) $connection['port'],
            '--username', (string) $connection['username'],
            '--dbname', (string) config('backup.maintenance_database'),
            '--set', 'ON_ERROR_STOP=1',
            '--command', 'DROP DATABASE '.$this->quoteIdentifier($targetDatabase).' WITH (FORCE)',
        ], ['PGPASSWORD' => (string) $connection['password']]);
    }

    /** @param array<string, mixed> $connection */
    private function assertTargetDatabase(string $targetDatabase, array $connection): void
    {
        $prefix = (string) config('backup.restore_database_prefix');
        $reserved = [(string) $connection['database'], (string) config('backup.maintenance_database')];

        if ($targetDatabase === '' || ! preg_match('/^[a-z][a-z0-9_]*$/', $targetDatabase) || ! Str::startsWith($targetDatabase, $prefix) || in_array($targetDatabase, $reserved, true)) {
            throw new RuntimeException('Restore target is not permitted.');
        }
    }

    private function assertRestoreEnvironment(): void
    {
        $allowedEnvironments = (array) config('backup.restore_allowed_environments', []);

        if (! in_array((string) config('app.env'), $allowedEnvironments, true)) {
            throw new RuntimeException('Restore is not permitted in this environment.');
        }
    }

    /** @param array<int, string> $command @param array<string, string> $environment */
    private function runOrFail(array $command, array $environment): void
    {
        if (! $this->processes->run($command, $environment)->successful()) {
            throw new RuntimeException('Restore process failed.');
        }
    }

    /** @return array<string, mixed> */
    private function connection(): array
    {
        $connection = config('database.connections.'.config('backup.database_connection'));

        if (! is_array($connection) || ($connection['driver'] ?? null) !== 'pgsql') {
            throw new RuntimeException('A PostgreSQL restore connection is required.');
        }

        foreach (['host', 'port', 'database', 'username', 'password'] as $key) {
            if (! array_key_exists($key, $connection) || $connection[$key] === null || $connection[$key] === '') {
                throw new RuntimeException('The restore connection configuration is incomplete.');
            }
        }

        return $connection;
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }
}
