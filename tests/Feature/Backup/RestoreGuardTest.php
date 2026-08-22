<?php

namespace Tests\Feature\Backup;

use App\Services\Backup\BackupEncryptionService;
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
            'backup.encryption.scheme' => 'AES-256-GCM-CHUNKED-V1',
            'database.connections.backup-test' => [
                'driver' => 'pgsql',
                'host' => '127.0.0.1',
                'port' => 5432,
                'database' => 'application_database',
                'username' => 'safe_user',
                'password' => 'never-in-command',
                'search_path' => 'public',
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

    public function test_uploaded_dump_is_verified_without_requiring_a_manifest_and_is_restored_only_to_a_disposable_database(): void
    {
        $artifact = $this->storageRoot.DIRECTORY_SEPARATOR.'uploaded.dump';
        File::put($artifact, 'uploaded-dump-content');
        $runner = new PostgreSqlProcessRunner(fn () => Process::result());

        $this->restoreService($runner)->drillUploaded($artifact);

        $commands = $runner->commands();

        $this->assertCount(6, $commands);
        $this->assertContains('--list', $commands[0]);
        $this->assertStringContainsString('siinv_restore_test_', implode(' ', $commands[1]));
        $this->assertStringContainsString('CREATE SCHEMA IF NOT EXISTS', implode(' ', $commands[2]));
        $this->assertStringContainsString('DROP DATABASE', implode(' ', $commands[5]));
    }

    public function test_uploaded_dump_is_validated_then_restored_to_the_configured_application_database(): void
    {
        $artifact = $this->storageRoot.DIRECTORY_SEPARATOR.'uploaded.dump';
        File::put($artifact, 'uploaded-dump-content');
        $runner = new PostgreSqlProcessRunner(fn () => Process::result());

        $this->restoreService($runner)->restoreUploadedToApplication($artifact);

        $commands = $runner->commands();

        $this->assertCount(4, $commands);
        $this->assertContains('--list', $commands[0]);
        $this->assertStringContainsString('CREATE SCHEMA IF NOT EXISTS', implode(' ', $commands[1]));
        $this->assertContains('--clean', $commands[2]);
        $this->assertContains('--if-exists', $commands[2]);
        $this->assertContains('--schema', $commands[2]);
        $this->assertContains('public', $commands[2]);
        $this->assertContains('application_database', $commands[2]);
        $this->assertStringContainsString("to_regclass('public.migrations')", implode(' ', $commands[3]));
    }

    public function test_uploaded_supabase_dump_restores_only_the_selected_application_schema(): void
    {
        $artifact = $this->storageRoot.DIRECTORY_SEPARATOR.'supabase.dump';
        File::put($artifact, 'uploaded-dump-content');
        $runner = new PostgreSqlProcessRunner(fn () => Process::result());

        $restoredSchema = $this->restoreService($runner)->restoreUploadedToApplication($artifact, 'laravel');
        $commands = $runner->commands();

        $this->assertSame('laravel', $restoredSchema);
        $this->assertStringContainsString('CREATE SCHEMA IF NOT EXISTS', implode(' ', $commands[1]));
        $this->assertContains('--schema', $commands[2]);
        $this->assertContains('laravel', $commands[2]);
        $this->assertStringContainsString("to_regclass('laravel.migrations')", implode(' ', $commands[3]));
        $schemaIndex = array_search('--schema', $commands[2], true);
        $this->assertSame('laravel', $commands[2][$schemaIndex + 1]);
    }

    public function test_uploaded_restore_rejects_reserved_supabase_schema_before_running_processes(): void
    {
        $artifact = $this->storageRoot.DIRECTORY_SEPARATOR.'supabase.dump';
        File::put($artifact, 'uploaded-dump-content');
        $runner = new PostgreSqlProcessRunner(fn () => Process::result());

        $this->expectException(RuntimeException::class);

        try {
            $this->restoreService($runner)->restoreUploadedToApplication($artifact, 'auth');
        } finally {
            $this->assertSame([], $runner->commands());
        }
    }

    public function test_uploaded_restore_fails_when_selected_schema_has_no_migrations_table(): void
    {
        $artifact = $this->storageRoot.DIRECTORY_SEPARATOR.'wrong-schema.dump';
        File::put($artifact, 'uploaded-dump-content');
        $runner = new PostgreSqlProcessRunner(function (array $command) {
            if (str_contains(implode(' ', $command), 'Application migrations table was not restored')) {
                return Process::result('', 'schema verification failed', 1);
            }

            return Process::result();
        });

        $this->expectException(RuntimeException::class);
        $this->restoreService($runner)->restoreUploadedToApplication($artifact, 'laravel');
    }

    public function test_encrypted_managed_backup_is_decrypted_only_for_the_restore_process(): void
    {
        config(['backup.encryption.key' => 'base64:'.base64_encode(random_bytes(32))]);
        $artifact = $this->encryptedArtifact();
        $observedContents = [];
        $runner = new PostgreSqlProcessRunner(function (array $command) use (&$observedContents) {
            foreach ($command as $argument) {
                if (is_string($argument) && is_file($argument) && basename($argument) === 'database.dump') {
                    $observedContents[] = File::get($argument);
                }
            }

            return Process::result();
        });

        $this->restoreService($runner)->restoreToApplication($artifact);

        $this->assertNotEmpty($observedContents);
        $this->assertSame(['encrypted-restore-fixture'], array_values(array_unique($observedContents)));
        $this->assertDirectoryDoesNotExist(
            $this->storageRoot.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'.tmp',
        );
    }

    private function validArtifact(): string
    {
        $directory = $this->storageRoot.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'artifact-safe';
        File::ensureDirectoryExists($directory);

        $artifact = $directory.DIRECTORY_SEPARATOR.'siinv-db-safe.dump';
        File::put($artifact, 'fixture-dump-content');

        (new BackupManifestService)->write($artifact, [
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

    private function encryptedArtifact(): string
    {
        $directory = $this->storageRoot.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'artifact-encrypted';
        File::ensureDirectoryExists($directory);
        $plain = $directory.DIRECTORY_SEPARATOR.'source.dump';
        $artifact = $directory.DIRECTORY_SEPARATOR.'siinv-db-safe.dump.enc';
        File::put($plain, 'encrypted-restore-fixture');
        (new BackupEncryptionService)->encryptFile($plain, $artifact);
        File::delete($plain);

        (new BackupManifestService)->write($artifact, [
            'backup_id' => 'artifact-encrypted',
            'created_at' => now()->toIso8601String(),
            'completed_at' => now()->toIso8601String(),
            'database_driver' => 'pgsql',
            'backup_format' => 'custom',
            'database_schema' => 'public',
            'compressed' => true,
            'encrypted' => true,
            'encryption_scheme' => 'AES-256-GCM-CHUNKED-V1',
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
            new BackupFilesystem,
            new BackupManifestService,
            $runner,
        );
    }
}
