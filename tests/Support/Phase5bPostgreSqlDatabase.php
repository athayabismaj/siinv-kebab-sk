<?php

namespace Tests\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\Process\Process;

final class Phase5bPostgreSqlDatabase
{
    private const PREFIX = 'siinv_fase5b_test_';

    private bool $created = false;

    private function __construct(
        private readonly string $database,
        private readonly array $connection,
        private readonly string $maintenanceDatabase,
        private readonly string $username,
        private readonly string $password,
        private readonly string $binaryDirectory,
    ) {}

    public static function fromEnvironment(): self
    {
        $connection = config('database.connections.fase4d');
        $required = ['host', 'port', 'database', 'username', 'password'];

        foreach ($required as $key) {
            if (blank($connection[$key] ?? null)) {
                throw new RuntimeException("Fase 5B PostgreSQL configuration is missing {$key}.");
            }
        }

        $host = (string) $connection['host'];
        if (! in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
            throw new RuntimeException('Fase 5B only permits a local PostgreSQL disposable database.');
        }

        $appDatabase = (string) config('database.connections.'.config('database.default').'.database');
        if ((string) $connection['database'] === $appDatabase) {
            throw new RuntimeException('Fase 5B PostgreSQL source must not be the application database.');
        }

        $binaryDirectory = (string) env('FASE5B_PG_BIN', 'C:\\Program Files\\PostgreSQL\\18\\bin');
        foreach (['createdb.exe', 'dropdb.exe', 'psql.exe'] as $binary) {
            if (! is_file($binaryDirectory.DIRECTORY_SEPARATOR.$binary)) {
                throw new RuntimeException("Fase 5B requires {$binary} in the configured PostgreSQL bin directory.");
            }
        }

        return new self(
            self::PREFIX.strtolower(bin2hex(random_bytes(8))),
            $connection,
            (string) env('FASE4D_PG_MAINTENANCE_DATABASE', 'postgres'),
            (string) $connection['username'],
            (string) $connection['password'],
            $binaryDirectory,
        );
    }

    public function createAndMigrate(): void
    {
        $this->run([
            $this->binary('createdb.exe'),
            '--host='.$this->connection['host'],
            '--port='.(string) $this->connection['port'],
            '--username='.$this->username,
            '--maintenance-db='.$this->maintenanceDatabase,
            $this->database,
        ])->mustRun();

        $this->created = true;
        $this->configureAsDefault();

        $exitCode = Artisan::call('migrate', [
            '--database' => 'fase5b',
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException('Fase 5B disposable PostgreSQL migration failed: '.Artisan::output());
        }
    }

    public function configureAsDefault(): void
    {
        config([
            'database.connections.fase5b' => [
                ...$this->connection,
                'database' => $this->database,
            ],
            'database.default' => 'fase5b',
        ]);

        DB::purge('fase5b');
        DB::setDefaultConnection('fase5b');
    }

    /** @return array<string, string> */
    public function workerEnvironment(): array
    {
        return [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'pgsql',
            'DB_HOST' => (string) $this->connection['host'],
            'DB_PORT' => (string) $this->connection['port'],
            'DB_DATABASE' => $this->database,
            'DB_USERNAME' => $this->username,
            'DB_PASSWORD' => $this->password,
            'CACHE_STORE' => 'array',
            'QUEUE_CONNECTION' => 'sync',
        ];
    }

    public function database(): string
    {
        return $this->database;
    }

    public function drop(): void
    {
        if (! $this->created || ! str_starts_with($this->database, self::PREFIX)) {
            return;
        }

        DB::disconnect('fase5b');

        $this->run([
            $this->binary('psql.exe'),
            '--host='.(string) $this->connection['host'],
            '--port='.(string) $this->connection['port'],
            '--username='.$this->username,
            '--dbname='.$this->maintenanceDatabase,
            '--command='.sprintf(
                "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '%s' AND pid <> pg_backend_pid()",
                str_replace("'", "''", $this->database),
            ),
        ])->mustRun();

        $this->run([
            $this->binary('dropdb.exe'),
            '--host='.(string) $this->connection['host'],
            '--port='.(string) $this->connection['port'],
            '--username='.$this->username,
            '--maintenance-db='.$this->maintenanceDatabase,
            '--if-exists',
            $this->database,
        ])->mustRun();

        $this->created = false;
    }

    private function binary(string $name): string
    {
        return $this->binaryDirectory.DIRECTORY_SEPARATOR.$name;
    }

    /** @param array<int, string> $command */
    private function run(array $command): Process
    {
        return (new Process($command, base_path(), [
            'PGPASSWORD' => $this->password,
        ]))->setTimeout(60);
    }
}
