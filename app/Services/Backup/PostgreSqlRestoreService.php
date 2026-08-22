<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class PostgreSqlRestoreService
{
    private readonly PostgreSqlApplicationSchema $schemas;

    private readonly BackupEncryptionService $encryption;

    public function __construct(
        private readonly BackupFilesystem $filesystem,
        private readonly BackupManifestService $manifests,
        private readonly PostgreSqlProcessRunner $processes,
        ?PostgreSqlApplicationSchema $schemas = null,
        ?BackupEncryptionService $encryption = null,
    ) {
        $this->schemas = $schemas ?? new PostgreSqlApplicationSchema;
        $this->encryption = $encryption ?? new BackupEncryptionService;
    }

    /** @return array{target_database:string,manifest:array<string,mixed>} */
    public function restoreTo(string $artifactPath, string $targetDatabase): array
    {
        $connection = $this->connection();
        $this->assertRestoreEnvironment();
        $this->assertTargetDatabase($targetDatabase, $connection);
        $manifest = $this->manifests->verify($artifactPath);
        $applicationSchema = $this->schemas->resolve(
            $connection,
            is_string($manifest['database_schema'] ?? null) ? $manifest['database_schema'] : null,
        );

        if (($manifest['database_driver'] ?? null) !== 'pgsql' || ($manifest['backup_format'] ?? null) !== 'custom') {
            throw new RuntimeException('Backup artifact is not supported for restore.');
        }

        return $this->withReadableArtifact($artifactPath, $manifest, function (string $restoreArtifact) use ($connection, $targetDatabase, $applicationSchema, $manifest): array {
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
                (string) config('backup.psql_path'),
                '--host', (string) $connection['host'],
                '--port', (string) $connection['port'],
                '--username', (string) $connection['username'],
                '--dbname', $targetDatabase,
                '--set', 'ON_ERROR_STOP=1',
                '--command', $this->createSchemaCommand($applicationSchema),
            ], $environment);

            $this->runOrFail([
                (string) config('backup.pg_restore_path'),
                '--exit-on-error',
                '--no-owner',
                '--no-privileges',
                '--schema', $applicationSchema,
                '--host', (string) $connection['host'],
                '--port', (string) $connection['port'],
                '--username', (string) $connection['username'],
                '--dbname', $targetDatabase,
                $restoreArtifact,
            ], $environment);

            $this->runOrFail([
                (string) config('backup.psql_path'),
                '--host', (string) $connection['host'],
                '--port', (string) $connection['port'],
                '--username', (string) $connection['username'],
                '--dbname', $targetDatabase,
                '--set', 'ON_ERROR_STOP=1',
                '--command', $this->migrationTableCheck($applicationSchema),
            ], $environment);

            return ['target_database' => $targetDatabase, 'manifest' => $manifest];
        });
    }

    /** @return array{target_database:string,manifest:array<string,mixed>} */
    public function drill(string $artifactPath): array
    {
        $targetDatabase = (string) config('backup.restore_database_prefix').Str::lower(Str::random(20));
        $result = $this->restoreTo($artifactPath, $targetDatabase);

        $this->dropDisposableDatabase($targetDatabase);

        return $result;
    }

    public function drillUploaded(string $artifactPath, ?string $applicationSchema = null): void
    {
        $connection = $this->connection();
        $this->assertRestoreEnvironment();
        $this->assertUploadedArtifact($artifactPath);
        $applicationSchema = $this->schemas->resolve($connection, $applicationSchema);

        $environment = ['PGPASSWORD' => (string) $connection['password']];

        // Validate the uploaded archive before PostgreSQL creates a disposable database.
        $this->runOrFail([
            (string) config('backup.pg_restore_path'),
            '--list',
            $artifactPath,
        ], $environment);

        $targetDatabase = (string) config('backup.restore_database_prefix').Str::lower(Str::random(20));
        $this->assertTargetDatabase($targetDatabase, $connection);
        $databaseCreated = false;

        try {
            $this->runOrFail([
                (string) config('backup.psql_path'),
                '--host', (string) $connection['host'],
                '--port', (string) $connection['port'],
                '--username', (string) $connection['username'],
                '--dbname', (string) config('backup.maintenance_database'),
                '--set', 'ON_ERROR_STOP=1',
                '--command', 'CREATE DATABASE '.$this->quoteIdentifier($targetDatabase).' OWNER '.$this->quoteIdentifier((string) $connection['username']),
            ], $environment);
            $databaseCreated = true;

            $this->runOrFail([
                (string) config('backup.psql_path'),
                '--host', (string) $connection['host'],
                '--port', (string) $connection['port'],
                '--username', (string) $connection['username'],
                '--dbname', $targetDatabase,
                '--set', 'ON_ERROR_STOP=1',
                '--command', $this->createSchemaCommand($applicationSchema),
            ], $environment);

            $this->runOrFail([
                (string) config('backup.pg_restore_path'),
                '--exit-on-error',
                '--no-owner',
                '--no-privileges',
                '--schema', $applicationSchema,
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
                '--command', $this->migrationTableCheck($applicationSchema),
            ], $environment);
        } finally {
            if ($databaseCreated) {
                $this->dropDisposableDatabase($targetDatabase);
            }
        }
    }

    public function restoreToApplication(string $artifactPath): string
    {
        $connection = $this->connection();
        $this->assertRestoreEnvironment();
        $manifest = $this->manifests->verify($artifactPath);
        $applicationSchema = $this->schemas->resolve(
            $connection,
            is_string($manifest['database_schema'] ?? null) ? $manifest['database_schema'] : null,
        );

        if (($manifest['database_driver'] ?? null) !== 'pgsql' || ($manifest['backup_format'] ?? null) !== 'custom') {
            throw new RuntimeException('Backup artifact is not supported for restore.');
        }

        $this->withReadableArtifact(
            $artifactPath,
            $manifest,
            fn (string $restoreArtifact) => $this->restoreArchiveToApplication($restoreArtifact, $connection, $applicationSchema),
        );

        return $applicationSchema;
    }

    public function restoreUploadedToApplication(string $artifactPath, ?string $applicationSchema = null): string
    {
        $connection = $this->connection();
        $this->assertRestoreEnvironment();
        $this->assertUploadedArtifact($artifactPath);
        $applicationSchema = $this->schemas->resolve($connection, $applicationSchema);

        $this->restoreArchiveToApplication($artifactPath, $connection, $applicationSchema);

        return $applicationSchema;
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

    private function assertUploadedArtifact(string $artifactPath): void
    {
        if (! is_file($artifactPath) || is_link($artifactPath) || filesize($artifactPath) <= 0) {
            throw new RuntimeException('Uploaded backup artifact is not valid.');
        }
    }

    /** @param array<string, mixed> $connection */
    private function restoreArchiveToApplication(string $artifactPath, array $connection, string $applicationSchema): void
    {
        $environment = ['PGPASSWORD' => (string) $connection['password']];

        // Validate the archive before changing the active application database.
        $this->runOrFail([
            (string) config('backup.pg_restore_path'),
            '--list',
            $artifactPath,
        ], $environment);

        $this->runOrFail([
            (string) config('backup.psql_path'),
            '--host', (string) $connection['host'],
            '--port', (string) $connection['port'],
            '--username', (string) $connection['username'],
            '--dbname', (string) $connection['database'],
            '--set', 'ON_ERROR_STOP=1',
            '--command', $this->createSchemaCommand($applicationSchema),
        ], $environment);

        $this->runOrFail([
            (string) config('backup.pg_restore_path'),
            '--clean',
            '--if-exists',
            '--exit-on-error',
            '--no-owner',
            '--no-privileges',
            '--schema', $applicationSchema,
            '--host', (string) $connection['host'],
            '--port', (string) $connection['port'],
            '--username', (string) $connection['username'],
            '--dbname', (string) $connection['database'],
            $artifactPath,
        ], $environment);

        $this->runOrFail([
            (string) config('backup.psql_path'),
            '--host', (string) $connection['host'],
            '--port', (string) $connection['port'],
            '--username', (string) $connection['username'],
            '--dbname', (string) $connection['database'],
            '--set', 'ON_ERROR_STOP=1',
            '--command', $this->migrationTableCheck($applicationSchema),
        ], $environment);
    }

    private function migrationTableCheck(string $applicationSchema): string
    {
        return "DO \$verify\$ BEGIN IF to_regclass('{$applicationSchema}.migrations') IS NULL THEN RAISE EXCEPTION 'Application migrations table was not restored'; END IF; END \$verify\$";
    }

    private function createSchemaCommand(string $applicationSchema): string
    {
        return 'CREATE SCHEMA IF NOT EXISTS '.$this->quoteIdentifier($applicationSchema);
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

    private function withReadableArtifact(string $artifactPath, array $manifest, callable $callback): mixed
    {
        if (($manifest['encrypted'] ?? false) !== true) {
            return $callback($artifactPath);
        }

        if (($manifest['encryption_scheme'] ?? null) !== (string) config('backup.encryption.scheme')) {
            throw new RuntimeException('Backup encryption scheme is not supported.');
        }

        $temporaryDirectory = $this->filesystem->temporaryDirectory('decrypt-'.Str::uuid());
        $decryptedPath = $temporaryDirectory.DIRECTORY_SEPARATOR.'database.dump';
        File::ensureDirectoryExists($temporaryDirectory);

        try {
            $this->encryption->decryptFile($artifactPath, $decryptedPath);

            return $callback($decryptedPath);
        } finally {
            $this->filesystem->deleteDirectory($temporaryDirectory);
        }
    }
}
