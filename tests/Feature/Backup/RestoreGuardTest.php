<?php

namespace Tests\Feature\Backup;

use App\Services\Backup\BackupFilesystem;
use App\Services\Backup\BackupManifestService;
use App\Services\Backup\PostgreSqlProcessRunner;
use App\Services\Backup\PostgreSqlRestoreService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Tests\TestCase;

class RestoreGuardTest extends TestCase
{
    private string $storageRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storageRoot = storage_path('framework/testing/restore-guard-'.uniqid('', true));
        File::ensureDirectoryExists($this->storageRoot);

        config([
            'filesystems.disks.local.root' => $this->storageRoot,
            'backup.disk' => 'local',
            'backup.directory' => 'backups',
            'backup.temporary_directory' => 'backups/.tmp',
            'backup.database_connection' => 'backup-test',
            'backup.pg_restore_path' => 'pg_restore',
            'backup.psql_path' => 'psql',
            'backup.timeout' => 30,
            'backup.restore_allowed_environments' => ['local', 'testing'],
            'backup.restore_database_prefix' => 'siinv_restore_test_',
            'backup.maintenance_database' => 'postgres',
            'database.connections.backup-test' => [
                'driver' => 'pgsql',
                'host' => '127.0.0.1',
                'port' => 5432,
                'database' => 'application_database',
                'username' => 'safe_user',
                'password' => 'never-in-command',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storageRoot);

        parent::tearDown();
    }

    public function test_restore_rejects_production_before_running_any_process(): void
    {
        config(['app.env' => 'production']);
        $runner = new PostgreSqlProcessRunner(fn () => Process::result());

        try {
            $this->restoreService($runner)->restoreTo($this->validArtifact(), 'siinv_restore_test_safe');
            $this->fail('Restore in production must be rejected.');
        } catch (RuntimeException) {
            // The assertion below proves the rejection happened before any process call.
        }

        $this->assertSame([], $runner->commands());
    }

    public function test_restore_rejects_active_database_and_invalid_disposable_names(): void
    {
        $runner = new PostgreSqlProcessRunner(fn () => Process::result());
        $service = $this->restoreService($runner);
        $artifact = $this->validArtifact();

        foreach (['', 'application_database', 'postgres', 'other_database', 'siinv_restore_test_bad-name'] as $target) {
            try {
                $service->restoreTo($artifact, $target);
                $this->fail("Restore target {$target} must be rejected.");
            } catch (RuntimeException) {
                $this->assertSame([], $runner->commands());
            }
        }
    }

    public function test_restore_rejects_checksum_mismatch_before_database_creation(): void
    {
        $artifact = $this->validArtifact();
        File::append($artifact, '-tampered');
        $runner = new PostgreSqlProcessRunner(fn () => Process::result());

        $this->expectException(RuntimeException::class);
        try {
            $this->restoreService($runner)->restoreTo($artifact, 'siinv_restore_test_safe');
        } finally {
            $this->assertSame([], $runner->commands());
        }
    }

    private function validArtifact(): string
    {
        $directory = $this->storageRoot.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'artifact-safe';
        File::ensureDirectoryExists($directory);

        $artifact = $directory.DIRECTORY_SEPARATOR.'siinv-db-safe.dump';
        File::put($artifact, 'fixture-dump-content');

        (new BackupManifestService())->write($artifact, [
            'backup_id' => 'artifact-safe',
            'created_at' => now()->toIso8601String(),
            'completed_at' => now()->toIso8601String(),
            'database_driver' => 'pgsql',
            'backup_format' => 'custom',
            'compressed' => true,
            'encrypted' => false,
            'checksum_algorithm' => 'sha256',
            'checksum' => hash_file('sha256', $artifact),
            'size_bytes' => filesize($artifact),
            'application_version' => 'test',
            'migration_state' => ['available' => false],
            'status' => 'success',
        ]);

        return $artifact;
    }

    private function restoreService(PostgreSqlProcessRunner $runner): PostgreSqlRestoreService
    {
        return new PostgreSqlRestoreService(
            new BackupFilesystem(),
            new BackupManifestService(),
            $runner,
        );
    }
}
