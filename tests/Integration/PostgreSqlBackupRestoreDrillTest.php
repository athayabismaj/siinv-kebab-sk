<?php

namespace Tests\Integration;

use App\Services\Backup\BackupFilesystem;
use App\Services\Backup\BackupManifestService;
use App\Services\Backup\PostgreSqlBackupService;
use App\Services\Backup\PostgreSqlProcessRunner;
use App\Services\Backup\PostgreSqlRestoreService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PDO;
use Tests\TestCase;

class PostgreSqlBackupRestoreDrillTest extends TestCase
{
    private string $storageRoot;
    private ?string $targetDatabase = null;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['FASE4D_PG_HOST', 'FASE4D_PG_DATABASE', 'FASE4D_PG_USERNAME', 'FASE4D_PG_PASSWORD'] as $key) {
            $this->assertNotSame('', (string) env($key), "{$key} must be configured for the explicit Fase 4D drill.");
        }

        $this->storageRoot = storage_path('framework/testing/fase4d-drill-'.uniqid('', true));
        File::ensureDirectoryExists($this->storageRoot);
        config([
            'filesystems.disks.local.root' => $this->storageRoot,
            'backup.disk' => 'local',
            'backup.directory' => 'backups',
            'backup.temporary_directory' => 'backups/.tmp',
            'backup.database_connection' => 'fase4d',
            'backup.restore_allowed_environments' => ['testing'],
            'backup.restore_database_prefix' => (string) env('FASE4D_PG_DATABASE_PREFIX', 'siinv_restore_test_'),
            'backup.maintenance_database' => (string) env('FASE4D_PG_MAINTENANCE_DATABASE', 'postgres'),
            'backup.encryption.enabled' => false,
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storageRoot);
        parent::tearDown();
    }

    public function test_dump_restore_and_verify_use_only_the_declared_disposable_postgresql_databases(): void
    {
        $source = $this->pdo((string) env('FASE4D_PG_DATABASE'));
        $source->exec('CREATE TABLE IF NOT EXISTS migrations (id serial primary key, migration varchar(255), batch integer)');
        $source->exec('CREATE TABLE IF NOT EXISTS phase4d_backup_fixture (marker varchar(64) primary key)');
        $source->exec('TRUNCATE phase4d_backup_fixture');
        $marker = 'fixture-'.Str::lower(Str::random(16));
        $source->prepare('INSERT INTO phase4d_backup_fixture (marker) VALUES (?)')->execute([$marker]);

        $filesystem = new BackupFilesystem();
        $manifests = new BackupManifestService();
        $runner = new PostgreSqlProcessRunner();
        $backup = (new PostgreSqlBackupService($filesystem, $manifests, $runner))->create('integration-drill');
        $this->assertFileExists($backup['file_path']);
        $this->assertFileExists($backup['manifest_path']);

        $prefix = (string) config('backup.restore_database_prefix');
        $this->targetDatabase = $prefix.Str::lower(Str::random(20));
        $restore = new PostgreSqlRestoreService($filesystem, $manifests, $runner);

        try {
            $restore->restoreTo($backup['file_path'], $this->targetDatabase);
            $target = $this->pdo($this->targetDatabase);
            $statement = $target->prepare('SELECT marker FROM phase4d_backup_fixture WHERE marker = ?');
            $statement->execute([$marker]);

            $this->assertSame($marker, $statement->fetchColumn());
            $restore->dropDisposableDatabase($this->targetDatabase);
            $this->targetDatabase = null;
        } finally {
            $source->exec('DROP TABLE IF EXISTS phase4d_backup_fixture');
        }
    }

    public function test_uploaded_dump_is_verified_with_a_disposable_restore_drill_without_requiring_the_internal_manifest(): void
    {
        $filesystem = new BackupFilesystem();
        $manifests = new BackupManifestService();
        $runner = new PostgreSqlProcessRunner();
        $backup = (new PostgreSqlBackupService($filesystem, $manifests, $runner))->create('integration-upload-drill');
        $uploadPath = $filesystem->path('backups/.tmp/uploaded-backup.dump');
        File::ensureDirectoryExists(dirname($uploadPath));
        File::copy($backup['file_path'], $uploadPath);

        $before = $this->disposableDatabases();

        (new PostgreSqlRestoreService($filesystem, $manifests, $runner))->drillUploaded($uploadPath);

        $this->assertSame($before, $this->disposableDatabases());
    }

    private function pdo(string $database): PDO
    {
        return new PDO(
            'pgsql:host='.(string) env('FASE4D_PG_HOST').';port='.(string) env('FASE4D_PG_PORT', '5432').';dbname='.$database,
            (string) env('FASE4D_PG_USERNAME'),
            (string) env('FASE4D_PG_PASSWORD'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
    }

    /** @return array<int, string> */
    private function disposableDatabases(): array
    {
        $statement = $this->pdo((string) config('backup.maintenance_database'))->prepare(
            'SELECT datname FROM pg_database WHERE datname LIKE ? ORDER BY datname',
        );
        $statement->execute([(string) config('backup.restore_database_prefix').'%']);

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }
}
