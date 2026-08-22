<?php

namespace Tests\Feature\Backup;

use App\Services\Backup\BackupEncryptionService;
use App\Services\Backup\BackupFilesystem;
use App\Services\Backup\BackupManifestService;
use App\Services\Backup\PostgreSqlBackupService;
use App\Services\Backup\PostgreSqlProcessRunner;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Tests\TestCase;

class BackupWorkflowTest extends TestCase
{
    private string $storageRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storageRoot = storage_path('framework/testing/backup-workflow-'.uniqid('', true));
        File::ensureDirectoryExists($this->storageRoot);

        config([
            'filesystems.disks.local.root' => $this->storageRoot,
            'backup.enabled' => true,
            'backup.disk' => 'local',
            'backup.directory' => 'backups',
            'backup.temporary_directory' => 'backups/.tmp',
            'backup.database_connection' => 'backup-test',
            'backup.pg_dump_path' => 'pg_dump',
            'backup.timeout' => 30,
            'backup.encryption.enabled' => false,
            'database.connections.backup-test' => [
                'driver' => 'pgsql',
                'host' => '127.0.0.1',
                'port' => 5432,
                'database' => 'safe_fixture',
                'username' => 'safe_user',
                'password' => 'never-in-command',
                'search_path' => 'laravel',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storageRoot);

        parent::tearDown();
    }

    public function test_backup_publishes_manifest_and_checksum_only_after_a_non_empty_dump_exists(): void
    {
        $runner = new PostgreSqlProcessRunner(function (array $command) {
            $outputIndex = array_search('--file', $command, true) + 1;
            File::put($command[$outputIndex], 'fixture-dump-content');

            return Process::result();
        });

        $backup = $this->backupService($runner)->create('manual');

        $this->assertFileExists($backup['file_path']);
        $this->assertFileExists($backup['manifest_path']);
        $this->assertMatchesRegularExpression(
            '/^SK-\d{2}-[A-Za-z]+-\d{4}\.dump$/',
            basename($backup['file_path']),
        );
        $this->assertSame(hash_file('sha256', $backup['file_path']), $backup['manifest']['checksum']);
        $this->assertSame(filesize($backup['file_path']), $backup['manifest']['size_bytes']);
        $this->assertSame('laravel', $backup['manifest']['database_schema']);
        $this->assertContains('--schema', $runner->commands()[0]);
        $this->assertContains('laravel', $runner->commands()[0]);
        $this->assertDirectoryDoesNotExist($this->storageRoot.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'.tmp'.DIRECTORY_SEPARATOR.$backup['backup_id']);
        $this->assertStringNotContainsString('never-in-command', implode(' ', $runner->commands()[0]));
    }

    public function test_failed_dump_cleans_its_temporary_artifact(): void
    {
        $runner = new PostgreSqlProcessRunner(fn () => Process::result('', 'dump failed', 1));

        try {
            $this->backupService($runner)->create('manual');
            $this->fail('Backup failure must be surfaced.');
        } catch (RuntimeException) {
            $this->assertDirectoryDoesNotExist($this->storageRoot.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'.tmp');
            $this->assertDirectoryDoesNotExist($this->storageRoot.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'artifacts');
        }
    }

    public function test_failed_dump_records_process_diagnostics_in_the_server_log(): void
    {
        Log::spy();

        $runner = new PostgreSqlProcessRunner(
            fn () => Process::result('connection to server at 127.0.0.1 was refused', 'pg_dump: error:', 1),
        );

        try {
            $this->backupService($runner)->create('manual');
            $this->fail('Backup failure must be surfaced.');
        } catch (RuntimeException) {
            Log::shouldHaveReceived('warning')
                ->once()
                ->with('Database backup failed.', \Mockery::on(function (array $context): bool {
                    return ($context['process_exit_code'] ?? null) === 1
                        && ($context['process_error'] ?? null) === 'pg_dump: error:'
                        && ($context['process_output'] ?? null) === 'connection to server at 127.0.0.1 was refused';
                }));
        }
    }

    public function test_failed_dump_diagnostics_keep_postgresql_context_but_redact_password_values(): void
    {
        Log::spy();
        $secret = 'sensitive-pg-password';
        $runner = new PostgreSqlProcessRunner(
            fn () => Process::result('', "pg_dump: error: password authentication failed\nPGPASSWORD={$secret}", 1),
        );

        try {
            $this->backupService($runner)->create('manual');
            $this->fail('Backup failure must be surfaced.');
        } catch (RuntimeException) {
            Log::shouldHaveReceived('warning')
                ->once()
                ->with('Database backup failed.', \Mockery::on(function (array $context) use ($secret): bool {
                    $error = (string) ($context['process_error'] ?? '');

                    return str_contains($error, 'password authentication failed')
                        && str_contains($error, 'PGPASSWORD=[redacted]')
                        && ! str_contains($error, $secret)
                        && ($context['artifact_state'] ?? null) === 'missing';
                }));
        }
    }

    public function test_empty_dump_is_rejected_and_not_published(): void
    {
        $runner = new PostgreSqlProcessRunner(function (array $command) {
            $outputIndex = array_search('--file', $command, true) + 1;
            File::put($command[$outputIndex], '');

            return Process::result();
        });

        $this->expectException(RuntimeException::class);

        try {
            $this->backupService($runner)->create('manual');
        } finally {
            $this->assertDirectoryDoesNotExist($this->storageRoot.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'artifacts');
        }
    }

    public function test_encryption_requires_an_explicit_key_when_enabled(): void
    {
        config(['backup.encryption.enabled' => true, 'backup.encryption.key' => null]);

        $runner = new PostgreSqlProcessRunner(fn () => Process::result());

        $this->expectException(RuntimeException::class);
        $this->backupService($runner)->create('manual');
    }

    public function test_encrypted_backup_is_published_without_plaintext_artifact(): void
    {
        config([
            'backup.encryption.enabled' => true,
            'backup.encryption.key' => 'base64:'.base64_encode(random_bytes(32)),
        ]);
        $runner = new PostgreSqlProcessRunner(function (array $command) {
            $outputIndex = array_search('--file', $command, true) + 1;
            File::put($command[$outputIndex], 'encrypted-fixture-dump');

            return Process::result();
        });

        $backup = $this->backupService($runner)->create('manual');

        $this->assertStringEndsWith('.dump.enc', $backup['file_path']);
        $this->assertTrue($backup['manifest']['encrypted']);
        $this->assertSame('AES-256-GCM-CHUNKED-V1', $backup['manifest']['encryption_scheme']);
        $this->assertFileDoesNotExist(substr($backup['file_path'], 0, -4));

        $decrypted = $this->storageRoot.DIRECTORY_SEPARATOR.'decrypted.dump';
        (new BackupEncryptionService)->decryptFile($backup['file_path'], $decrypted);
        $this->assertSame('encrypted-fixture-dump', File::get($decrypted));
    }

    private function backupService(PostgreSqlProcessRunner $runner): PostgreSqlBackupService
    {
        return new PostgreSqlBackupService(
            new BackupFilesystem,
            new BackupManifestService,
            $runner,
        );
    }
}
